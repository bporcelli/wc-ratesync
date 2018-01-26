<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Installer.
 *
 * Handles plugin installation and updates.
 *
 * @author 	WC RateSync
 * @package WC_RateSync
 * @since 	0.0.1
 */
class WC_RS_Install {

	/**
	 * @var Registered update hooks.
	 */
	private static $update_hooks = array(
		'1.1.0' => array(
			array( __CLASS__, 'update_110_tax_states' )
		),
	);

	/**
	 * Initialize installer.
	 *
	 * @since 0.0.1
	 */
	public static function init() {
		add_filter( 'plugin_action_links_' . plugin_basename( RS_FILE ), array( __CLASS__, 'add_settings_link' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_update' ) );
	}

	/**
	 * Runs on admin_init and triggers a data update if one is needed.
	 *
	 * Right now, the update routines are lightweight and run in the
	 * foreground. If heavier update routines are introduced in the
	 * future, they should probably be moved to the background.
	 *
	 * @since 1.1.0
	 */
	public static function maybe_update() {
		$db_version = get_option( 'ratesync_db_version', '1.0.0' );

		if ( version_compare( $db_version, RS_VERSION, '>=' ) ) {
			return;
		}

		foreach ( self::$update_hooks as $version => $hooks ) {
			if ( version_compare( $db_version, $version, '<' ) ) {
				foreach ( $hooks as $hook ) {
					call_user_func( $hook );
				}
			}
		}

		WC_RS_Notices::add_custom( 'updated', 'success', __(
			'Thank you for updating to the latest version of RateSync.',
			'wc-ratesync'
		) );

		update_option( 'ratesync_db_version', RS_VERSION );
	}

	/**
	 * In 1.1.0, the data structure used to store the user's tax states was
	 * changed. This update hook takes care of migrating existing tax states
	 * to the new data structure.
	 *
	 * @since 1.1.0
	 */
	private static function update_110_tax_states() {
		$states         = WC()->countries->get_states( 'US' );
		$old_tax_states = get_option( 'ratesync_tax_states', array() );
		$new_tax_states = array();

		foreach ( $old_tax_states as $state_abbrev ) {
			$new_tax_states[] = array(
				'abbrev'               => $state_abbrev,
				'name'                 => $states[ $state_abbrev ],
				'shipping_tax_enabled' => 'yes',
			);
		}

		wc_rs_set_tax_states( $new_tax_states );
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
	 * Schedule a daily rate sync starting 12:00AM tomorrow.
	 *
	 * @since 0.0.1
	 */
	protected static function schedule_sync() {
		$start_time = mktime( 0, 0, 0, date( 'n' ), date( 'j' ) + 1 );
		wp_schedule_event( $start_time, 'daily', 'wc_rs_sync' );
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