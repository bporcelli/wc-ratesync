<?php

/**
 * Plugin Name: WC RateSync
 * Description: The easiest way to keep your WooCommerce tax tables up-to-date.
 * Version: 1.0.0
 * Author: WC RateSync
 * Author URI: http://wcratesync.com
 *
 * @package WC_RateSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// WooCommerce not active or unsupported? Bail.
if ( ! function_exists( 'is_plugin_active' ) ) {
	require ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) || version_compare( get_option( 'woocommerce_db_version' ), '2.3.0', '<' ) ) {
	add_action( 'admin_notices', array( 'WC_RateSync', 'woocommerce_notice' ) );
	return;
}

/**
 * RateSync.
 *
 * Main plugin class.
 *
 * @author 	WC RateSync
 * @package WC_RateSync
 * @since 	0.0.1
 */
final class WC_RateSync {

	/**
	 * @var Current plugin version
	 */
	public $version = '1.0.0';

	/**
	 * @var RateSync instance
	 */
	protected static $_instance = null;

	/**
	 * Singleton instance accessor.
	 *
	 * @since 0.0.1
	 * 
	 * @return WC_RateSync
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor. Bootstraps the plugin.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->load_textdomain();
		$this->define_constants();
		$this->includes();
		$this->hooks();
		$this->check_updates();
	}

	/**
	 * Load text domain.
	 *
	 * @since 0.0.1
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'wc-ratesync', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Define plugin constants.
	 *
	 * @since 0.0.1
	 */
	private function define_constants() {
		define( 'RS_SL_STORE_URL', 'http://wcratesync.com' );
		define( 'RS_SL_ITEM_ID', 2378 );
		define( 'RS_FILE', __FILE__ );
	}

	/**
	 * Include plugin files.
	 *
	 * @since 0.0.1
	 */
	private function includes() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			require_once 'includes/vendor/EDD_SL_Plugin_Updater.php';
		}
		require_once 'includes/class-wc-rs-notices.php';
		require_once 'includes/class-wc-rs-install.php';
		require_once 'includes/class-wc-rs-sync.php';
		require_once 'includes/class-wc-rs-settings.php';
	}

	/**
	 * Register action hooks.
	 *
	 * @since 0.0.1
	 */
	private function hooks() {
		register_activation_hook( __FILE__, array( 'WC_RS_Install', 'activate' ) );
		register_deactivation_hook( __FILE__, array( 'WC_RS_Install', 'deactivate' ) );
	}

	/**
	 * Check for plugin updates.
	 *
	 * @since 0.0.1
	 */
	private function check_updates() {
		// Retrieve license key
		$license_key = trim( get_option( 'ratesync_license_key' ) ); 
		
		// Instantiate updater
		$edd_updater = new EDD_SL_Plugin_Updater( RS_SL_STORE_URL, __FILE__, array(
			'version' => $this->version,
			'license' => $license_key,
			'item_id' => RS_SL_ITEM_ID,
			'author'  => 'WC RateSync',
			'url'	  => home_url(),
		    'beta'    => false,
		) );
	}

	/**
	 * Display a notice if WooCommerce is inactive or an unsupported version
	 * is installed.
	 *
	 * @since 0.0.1
	 */
	public static function woocommerce_notice() {
		printf( '<div class="notice notice-error"><p>%s</p></div>', __( '<strong>WC RateSync is inactive.</strong> WooCommerce 2.3 or greater is required.', 'wc-ratesync' ) );
	}

	/**
	 * Return plugin dir path without a trailing slash.
	 *
	 * @since 0.0.1
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Return plugin dir url without a trailing slash.
	 *
	 * @since 0.0.1
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

}

/**
 * Return the plugin instance.
 *
 * @since 0.0.1
 *
 * @return WC_RateSync
 */
function RateSync() {
	return WC_RateSync::instance();
}

RateSync();