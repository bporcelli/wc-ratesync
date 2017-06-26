<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Admin notices.
 *
 * Methods for displaying admin notices.
 *
 * @author 	WC RateSync
 * @package WC_RateSync
 * @since 	0.0.1
 */
class WC_RS_Notices {

	/**
	 * @var Map from notice IDs to callbacks.
	 */
	protected static $notices = array(
		'configure' => 'configure_notice',
	);

	/**
	 * Register action hooks.
	 *
	 * @since 0.0.1
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'display' ) );
		add_action( 'admin_init', array( __CLASS__, 'dismiss' ) );
	}

	/**
	 * Display admin notices.
	 *
	 * @since 0.0.1
	 */
	public static function display() {
		$notices = get_option( 'wc_rs_notices', array() );

		foreach ( $notices as $notice_id => $notice ) {
			if ( array_key_exists( $notice_id, self::$notices ) ) {
				call_user_func( array( __CLASS__, self::$notices[ $notice_id ] ) );
			} else {
				include RateSync()->plugin_path() . '/views/admin/html-notice-custom.php';
			}
		}
	}

	/**
	 * Dismiss a notice (maybe).
	 *
	 * @since 0.0.1
	 */
	public static function dismiss() {
		$notices = get_option( 'wc_rs_notices', array() );

		if ( isset( $_GET['rs_dismiss_notice'], $notices[ $_GET['rs_dismiss_notice'] ] ) ) {
			unset( $notices[ $_GET['rs_dismiss_notice'] ] );
			update_option( 'wc_rs_notices', $notices );
		}
	}

	/**
	 * Add a notice.
	 *
	 * @since 0.0.1
	 *
	 * @param string $id Notice ID.
	 */
	public static function add( $id ) {
		$notices = get_option( 'wc_rs_notices', array() );
		
		$notices[ $id ] = true;
		
		update_option( 'wc_rs_notices', $notices );
	}

	/**
	 * Add a custom notice.
	 *
	 * @since 0.0.1
	 *
	 * @param string $id Notice ID.
	 * @param string $type Notice type (error, success).
	 * @param string $content Notice content.
	 */
	public static function add_custom( $id, $type, $content ) {
		$notices = get_option( 'wc_rs_notices', array() );

		$_notice = array(
			'type'    => $type,
			'content' => $content,
		);

		$notices[ $id ] = $_notice;

		update_option( 'wc_rs_notices', $notices );
	}

	/**
	 * Remove a notice.
	 *
	 * @since 0.0.1
	 *
	 * @param string $id Notice ID.
	 */
	public static function remove( $id ) {
		$notices = get_option( 'wc_rs_notices', array() );

		if ( array_key_exists( $id, $notices ) ) {
			unset( $notices[ $id] );
		}

		update_option( 'wc_rs_notices', $notices );
	}

	/**
	 * Plugin configuration notice.
	 *
	 * @since 0.0.1
	 */
	public static function configure_notice() {
		$screen = get_current_screen();
		$tab    = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

		if ( 'woocommerce_page_wc-settings' !== $screen->id  || 'tax' !== $tab ) {
			include RateSync()->plugin_path() . '/views/admin/html-notice-configure.php';
		}
	}

}

WC_RS_Notices::init();