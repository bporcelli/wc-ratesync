<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WC_RS_Sync_Exception' ) ) {
	require __DIR__ . '/class-wc-rs-sync-exception.php';
}

/**
 * Sync.
 *
 * Responsible for downloading and importing the latest rates from TaxRates.com.
 *
 * @author 	WC RateSync
 * @package WC_RateSync
 * @since 	0.0.1
 */
class WC_RS_Sync {

	const API_URL = 'http://wcratesync.com/rs-api';

	/**
	 * @var WC_Tax_Rate_Importer instance.
	 */
	protected $importer;

	/**
	 * Constructor. Registers action hooks.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 100 );
		add_action( 'wc_rs_sync', array( $this, 'start' ) );
		add_action( 'wp_ajax_wc_rs_import', array( $this, 'import' ) );
		add_action( 'wp_ajax_nopriv_wc_rs_import', array( $this, 'import' ) );
	}

	/**
	 * Include required files.
	 *
	 * @since 0.0.1
	 */
	public function includes() {
		if ( ! class_exists( 'WP_Importer' ) ) {
			include ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		}
		if ( ! class_exists( 'WC_Tax_Rate_Importer' ) ) {
			include WC()->plugin_path() . '/includes/admin/importers/class-wc-tax-rate-importer.php';
		}
	}

	/**
	 * Initialize.
	 *
	 * @since 0.0.1
	 */
	public function init() {
		$this->includes();
		$this->importer = new WC_Tax_Rate_Importer();
	}

	/**
	 * Send async request to start table import.
	 *
	 * @since 0.0.1
	 */
	protected function trigger_import() {
		$request_uri = admin_url( 'admin-ajax.php' );
		
		$response = wp_remote_post( $request_uri, array(
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'body'      => array( 'action' => 'wc_rs_import' ),
		) );
	}

	/**
	 * Start rate sync.
	 *
     * Cancels sync in progress (if any) and starts a new one.
	 *
	 * @since 0.0.1
	 */
	public function start() {
		if ( $this->in_progress() ) {
			$this->cancel( false );
		}

		$license = trim( get_option( 'ratesync_license_key' ) );

		if ( empty( $license ) ) {
			$this->add_error( __( 'A valid license key is required.', 'wc-ratesync' ) );
		}

		update_option( 'ratesync_sync_status', 'in_progress' );
		update_option( 'ratesync_last_sync', time() );
		update_option( 'ratesync_sync_queue', wc_rs_get_tax_states() );
		
		$this->trigger_import();
	}

	/**
	 * Download the latest tax rate table for a state and return its path.
	 *
	 * @since 1.1.0
	 *
	 * @throws WC_RS_Sync_Exception If the download fails.
	 * @param  string $state State abbrevation.
	 * @return boolean
	 */
	private function download_table( $state ) {
		$upload_dir = wp_upload_dir();

		$table_path = $upload_dir[ 'basedir' ] . '/ratesync_tables/' . $state . '.csv';
		$table_hash = file_exists( $table_path ) ? md5_file( $table_path ) : '';

		// Download table to temporary path and move to $table_path on success
		$response = wp_remote_get( self::API_URL . '/table/' . $state, array(
			'timeout' => 20,
			'stream'  => true,
			'headers' => array(
				'X-RS-License' => get_option( 'ratesync_license_key' ),
				'X-RS-Hash'    => $table_hash,
			),
		) );

		if ( is_wp_error( $response ) ) {
			throw new WC_RS_Sync_Exception( $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 != $status_code && 304 != $status_code ) {
			$message = sprintf(
				/** translators: state abbreviation, HTTP response code */
				__( "Couldn't retrieve table for state %s (response code: %d).", 'wc-ratesync' ),
				$state,
				$status_code
			);
			throw new WC_RS_Sync_Exception( $message );
		}
		if ( 200 === $status_code ) {
			rename( $response[ 'filename' ], $table_path );
		}
		return $table_path;
	}

	/**
	 * Import table.
	 *
	 * Remove a state from the sync queue, import the table for the state, and
	 * start import for next state if any remain.
	 *
	 * @since 0.0.1
	 */
	public function import() {
		global $wpdb;

		$queue = get_option( 'ratesync_sync_queue', array() );

		if ( empty( $queue ) ) {  // Done processing
			$this->complete();
		}

		$wc_tax_rates = "{$wpdb->prefix}woocommerce_tax_rates";
		$state        = array_pop( $queue );

		try {
			$table_path = $this->download_table( $state[ 'abbrev' ] );

			$wpdb->delete( $wc_tax_rates, array(
				'tax_rate_state' => $state[ 'abbrev' ],
				'tax_rate_class' => '',
			) );

			$this->importer->import( $table_path );

			/* By default, the 'shipping' flag is set for all imported rates. If
			 * the user has disabled shipping tax for the current state, we need
			 * to unset it. */
			if ( 'no' === $state[ 'shipping_taxable' ] ) {
				$set   = array( 'tax_rate_shipping' => 0 );
				$where = array( 'tax_rate_state' => $state[ 'abbrev' ] );

				$wpdb->update( $wc_tax_rates, $set, $where );
			}
		} catch ( WC_RS_Sync_Exception $e ) {
			$this->add_error( $e->getMessage() );
		}

		// Continue processing
		update_option( 'ratesync_sync_queue', $queue );

		$this->trigger_import();
	}

	/**
	 * Cancel sync.
	 *
	 * @since 0.0.1
	 *
	 * @param bool $and_die Die after cancelled? (default: true)
	 */
	protected function cancel( $and_die = true ) {
		update_option( 'ratesync_sync_queue', array() );
		update_option( 'ratesync_sync_status', 'aborted' );
		
		if ( $and_die ) {
			wp_die();
		}
	}

	/**
	 * Complete sync.
	 *
	 * Update sync status and delete orphaned tax rate locations.
	 *
	 * @since 0.0.1
	 */
	protected function complete() {
		global $wpdb;

		update_option( 'ratesync_sync_status', 'complete' );

		$wpdb->query( "
			DELETE l
			FROM {$wpdb->prefix}woocommerce_tax_rate_locations l
			WHERE l.location_id > 0
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = l.tax_rate_id
			);
		" );

		wp_die();
	}

	/**
	 * Is a sync in progress?
	 *
	 * @since 0.0.1
	 *
	 * @return bool
	 */
	protected function in_progress() {
		return 'in_progress' === get_option( 'ratesync_sync_status' );
	}

	/**
	 * Show error message and cancel sync.
	 *
	 * @since 0.0.1
	 *
	 * @param string $message
	 */
	protected function add_error( $message ) {
		WC_RS_Notices::add_custom( 'ratesync_status', 'error', sprintf( __( 'Tax rate sync failed: %s', 'wc-ratesync' ), $message ) );

		$this->cancel();
	}

	/**
	 * Show message.
	 *
	 * @since 0.0.1
	 *
	 * @param string $message
	 */
	protected function add_message( $message ) {
		WC_RS_Notices::add_custom( 'ratesync_status', 'success', $message );
	}

}

new WC_RS_Sync();