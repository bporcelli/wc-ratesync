<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
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

		// Check: license entered?
		$license = trim( get_option( 'ratesync_license_key' ) );

		if ( empty( $license ) ) {
			$this->add_error( __( 'A valid license key is required.', 'wc-ratesync' ), false );
			return;
		}

		// Start sync
		$states = WC_Admin_Settings::get_option( 'ratesync_tax_states', array() );

		update_option( 'ratesync_sync_status', 'in_progress' );
		update_option( 'ratesync_last_sync', time() );
		update_option( 'ratesync_sync_queue', $states );
		
		$this->trigger_import();
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

		// Done processing?
		if ( empty( $queue ) ) {
			$this->complete();
		}

		// Get local rate table path
		$state      = array_pop( $queue );
		$upload_dir = wp_upload_dir();
		$table_path = $upload_dir['basedir'] . '/ratesync_tables/' . $state . '.csv';

		// Download rate table if changed
		$temp_path  = tempnam( sys_get_temp_dir(), 'RS' );
		$table_hash = file_exists( $table_path ) ? md5_file( $table_path ) : '';

		$response = wp_remote_get( self::API_URL . '/table/' . $state, array(
			'timeout'  => 20,
			'stream'   => true,
			'filename' => $temp_path,
			'headers'  => array( 
				'X-RS-License' => get_option( 'ratesync_license_key' ),
				'X-RS-Hash'    => $table_hash,
			),
		) );

		// Check response for errors
		if ( is_wp_error( $response ) ) {
			$this->add_error( $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 403 == $status_code ) {
			$this->add_error( __( "License key inactive, disabled, or expired.", 'wc-ratesync' ) );
		} else if ( 200 != $status_code && 304 != $status_code ) {
			$this->add_error( __( "Couldn't retrieve table for state $state (response code: $status_code).", 'wc-ratesync' ) );
		}

		// Import rate table, replacing existing rates
		if ( 200 == $status_code ) {
			rename( $temp_path, $table_path );
		}

		$wpdb->delete( $wpdb->prefix . 'woocommerce_tax_rates', array(
			'tax_rate_state' => $state,
		) );

		$this->importer->import( $table_path );

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
	 * @since 0.0.1
	 */
	protected function complete() {
		update_option( 'ratesync_sync_status', 'complete' );
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
	 * @param bool $and_die Die after canceled? (default: true)
	 */
	protected function add_error( $message, $and_die = true ) {
		WC_RS_Notices::add_custom( 'ratesync_status', 'error', sprintf( __( 'Tax rate sync failed: %s', 'wc-ratesync' ), $message ) );

		$this->cancel( $and_die );
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