<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Installer.
 *
 * Handles plugin installation.
 *
 * @author 	Brett Porcelli
 * @package WC_RateSync
 * @since 	0.0.1
 */
class WC_RS_Install {

	/**
	 * Initialize installer.
	 *
	 * @since 0.0.1
	 */
	public static function init() {
		add_filter( 'plugin_action_links_' . plugin_basename( RS_FILE ), array( __CLASS__, 'add_settings_link' ) );
	}

	/**
	 * Activation routine. Installs the plugin.
	 *
	 * @since 0.0.1
	 */
	public static function activate() {
		self::create_folder();
		self::schedule_sync();
		self::configure_woocommerce();
	}

	/**
	 * Deactivation routine.
	 *
	 * @since 0.0.1
	 */
	public static function deactivate() {
		self::unschedule_sync();
	}

	/**
	 * Configure WooCommerce tax settings.
	 *
	 * @since 0.0.1
	 */
	protected static function configure_woocommerce() {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_total_display', 'single' );
	}

	/**
	 * Create a folder for storing tax rate .CSVs.
	 *
	 * @since 0.0.1
	 */
	protected static function create_folder() {
		$upload_dir = wp_upload_dir();
		$tables_dir = $upload_dir['basedir'] . '/ratesync_tables';

		if ( ! file_exists( $tables_dir ) && ! mkdir( $tables_dir, 0755 ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( "RateSync install failed: couldn't create directory $tables_dir.", 'wc-ratesync' ) );
		}
	}

	/**
	 * Schedule a daily rate sync starting 12:00AM today.
	 *
	 * @since 0.0.1
	 */
	protected static function schedule_sync() {
		wp_schedule_event( mktime( 0, 0, 0 ), 'daily', 'wc_rs_sync' );
	}

	/**
	 * Unschedule monthly rate sync.
	 *
	 * @since 0.0.1
	 */
	protected static function unschedule_sync() {
		wp_clear_scheduled_hook( 'wc_rs_sync' );
	}

	/**
	 * Add Settings link to plugin actions.
	 *
	 * @since 0.0.1
	 *
	 * @param  array $actions Current plugin actions.
	 * @return array
	 */
	public static function add_settings_link( $actions ) {
		$action = array(
			'settings' => '<a href="'. admin_url( 'admin.php?page=wc-settings&tab=tax#ratesync_options' ) .'">Settings</a>'
		);
		return array_merge( $action, $actions );
	}

}

WC_RS_Install::init();