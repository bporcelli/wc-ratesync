<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sync.
 *
 * Responsible for downloading and importing the latest rates from TaxRates.com.
 *
 * @author 	Brett Porcelli
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
			$this->cancel();
		}

		$states  = WC_Admin_Settings::get_option( 'ratesync_tax_states', array() );
		$license = trim( get_option( 'ratesync_license_key' ) );

		// Check: license entered?
		if ( empty( $license ) ) {
			$this->add_error( __( 'Tax rate sync failed: A valid license key is required.', 'wc-ratesync' ) );
			wp_die();
		}

		// Start sync
		$this->add_message( __( 'Tax rate sync in progress.', 'wc-ratesync' ) );
		
		update_option( 'ratesync_sync_status', 'in_progress' );
		update_option( 'ratesync_last_sync', time() );
		update_option( 'ratesync_last_sync_states', $states );
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

		// TODO: WHY ISN'T SYNC PROGRESSING?
		$queue = get_option( 'ratesync_sync_queue', array() );

		// Done processing?
		if ( empty( $queue ) ) {
			$this->add_message( __( 'Tax rates synced successfully.', 'wc-ratesync' ) );
			
			update_option( 'ratesync_sync_status', 'complete' );
			wp_die();
		}

		$state      = array_pop( $queue );
		$license    = get_option( 'ratesync_license_key' );
		$upload_dir = wp_upload_dir();

		// Download rate table for state
		$table_path = $upload_dir['basedir'] . '/ratesync_tables/' . $state . '.csv';

		$response = wp_remote_get( self::API_URL . '/table/' . $state, array(
			'timeout'  => 20,
			'stream'   => true,
			'filename' => $table_path,
			'headers'  => array( 'X-RS-License' => $license ),
		) );

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || 200 != $status_code ) {
			$this->add_error( __( "Tax rate sync failed: couldn't retrieve table for state $state (response code: $status_code). Please check your license key and try again.", 'wc-ratesync' ) );
			wp_die();
		}

		// Import table, replacing existing rates
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
	 */
	protected function cancel() {
		update_option( 'ratesync_sync_queue', array() );
		update_option( 'ratesync_sync_status', 'stopped' );
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
		WC_RS_Notices::add_custom( 'ratesync_status', 'error', $message );

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