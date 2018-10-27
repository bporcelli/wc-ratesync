<?php

/**
 * Plugin Name:          WC RateSync
 * Description:          The easiest way to keep your WooCommerce tax tables up-to-date.
 * Version:              1.1.4
 * Author:               WC RateSync
 * Author URI:           http://wcratesync.com
 * Requires at least:    4.4.0
 * Tested up to:         4.9.0
 * WC requires at least: 2.3.0
 * WC tested up to:      3.5.0
 * Text Domain:          wc-ratesync
 * Domain Path:          /languages
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
 */
final class WC_RateSync {

	/**
	 * @var string Current plugin version
	 */
	public $version = '1.1.4';

	/**
	 * @var bool Is the plugin in debug mode?
	 */
	private $debug = false;

	/**
	 * @var WC_RateSync RateSync instance
	 */
	protected static $_instance = null;

	/**
	 * Singleton instance accessor.
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
	 */
	private function load_textdomain() {
		load_plugin_textdomain( 'wc-ratesync', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		define( 'RS_SL_STORE_URL', 'http://wcratesync.com' );
		define( 'RS_SL_ITEM_ID', 2378 );
		define( 'RS_FILE', __FILE__ );
		define( 'RS_VERSION', $this->version );
		define( 'RS_DEBUG', $this->debug );
	}

	/**
	 * Include plugin files.
	 */
	private function includes() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			require_once 'includes/vendor/EDD_SL_Plugin_Updater.php';
		}
		require_once 'includes/wc-rs-functions.php';
		require_once 'includes/class-wc-rs-notices.php';
		require_once 'includes/class-wc-rs-states.php';
		require_once 'includes/class-wc-rs-install.php';
		require_once 'includes/class-wc-rs-sync.php';
		require_once 'includes/class-wc-rs-settings.php';
	}

	/**
	 * Register action hooks.
	 */
	private function hooks() {
		register_activation_hook( __FILE__, array( 'WC_RS_Install', 'activate' ) );
		register_deactivation_hook( __FILE__, array( 'WC_RS_Install', 'deactivate' ) );
		add_filter( 'woocommerce_get_tax_location', array( $this, 'handle_zip4_codes' ) );
	}

	/**
	 * Check for plugin updates.
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
	 */
	public static function woocommerce_notice() {
		echo '<div class="notice notice-error"><p>';
		printf(
			__( '%1$sWC RateSync is inactive.%2$s WooCommerce 2.3 or greater is required.', 'wc-ratesync' ),
			'<strong>',
			'</strong>'
		);
		echo '</p></div>';
	}

	/**
	 * Return plugin dir path without a trailing slash.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Return plugin dir url without a trailing slash.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
     * Filters the WC tax location so that only the first 5 digits of the
     * ZIP code are used to match tax rates.
     *
     * @param array $location
     *
     * @return array
     */
	public function handle_zip4_codes( $location ) {
	    list( $country, $state, $postcode, $city ) = $location;

	    if ( 'US' === $country ) {
	        $location[2] = substr( $postcode, 0, 5 );
        }
        return $location;
    }

}

/**
 * Return the plugin instance.
 *
 * @return WC_RateSync
 */
function RateSync() {
	return WC_RateSync::instance();
}

RateSync();