<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * RateSync settings.
 *
 * Methods for rendering settings fields and saving settings.
 *
 * @author 	WC RateSync
 * @package WC_RateSync
 * @since 	0.0.1
 */
class WC_RS_Settings {

	/**
	 * Constructor. Registers hooks.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'admin_init', array( $this, 'maybe_deactivate_license' ) );
		add_action( 'admin_print_styles', array( $this, 'add_config_notice' ) );
		add_action( 'woocommerce_settings_ratesync_options', array( $this, 'add_settings_anchor' ) );
		add_filter( 'woocommerce_tax_settings', array( $this, 'add_settings' ) );
		add_action( 'woocommerce_admin_field_rs_tax_states', array( $this, 'output_tax_states' ) );
		add_action( 'woocommerce_admin_field_rs_sync_status', array( $this, 'output_sync_status' ) );
		add_action( 'woocommerce_admin_field_rs_license', array( $this, 'output_license_key' ) );
		add_action( 'woocommerce_update_options_tax', array( $this, 'save_settings' ) );
	}

	/**
	 * Nag the user to activate their license and configure their tax states if
	 * they haven't already.
	 *
	 * @since 0.0.1
	 */
	public function add_config_notice() {
		$tax_states     = wc_rs_get_tax_states();
		$license_active = $this->license_active();

		if ( ! $license_active || empty( $tax_states ) ) {
			WC_RS_Notices::add( 'configure' );
		} else {
			WC_RS_Notices::remove( 'configure' );
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_scripts_and_styles() {
		wc_rs_enqueue_style( 'wc-rs-admin', 'admin' );

		wc_rs_register_script( 'wc-rs-tax-states', 'wc-rs-tax-states', [
			'jquery',
			'wp-util',
			'underscore',
			'backbone',
			'wc-backbone-modal'
		], RS_VERSION );
	}

	/**
	 * Add anchor point so we can link to our section of the Tax settings page.
	 *
	 * @since 0.0.1
	 */
	public function add_settings_anchor() {
		echo '<span id="ratesync_options"></span>';
	}

	/**
	 * Add RateSync settings.
	 *
	 * @since 0.0.1
	 *
	 * @param  array $settings Existing tax settings.
	 *
	 * @return array
	 */
	public function add_settings( $settings ) {
		$settings[] = [
			'title' => __( 'RateSync options', 'wc-ratesync' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'ratesync_options'
		];

		$settings[] = [
			'title'       => __( 'License key', 'wc-ratesync' ),
			'id'          => 'ratesync_license_key',
			'default'     => '',
			'placeholder' => 'a12f35kasdc4673kdcbs215678',
			'type'        => 'rs_license',
			'desc_tip'    => __( 'Enter your RateSync license key. This was included in your download email.', 'wc-ratesync' ),
		];

		$settings[] = [
			'title'    => __( 'Last sync', 'wc-ratesync' ),
			'id'       => 'ratesync_last_sync',
			'type'     => 'rs_sync_status',
			'desc_tip' => __( 'The time and status of the last rate sync. Rates will be synced once daily and when you save your tax settings.', 'wc-ratesync' ),
		];

		$settings[] = [
			'title'    => __( 'Tax states', 'wc-ratesync' ),
			'id'       => 'ratesync_tax_states',
			'default'  => '',
			'type'     => 'rs_tax_states',
			'desc_tip' => __( 'Add all states where you need to collect tax. If you are at all unsure, please consult a tax professional.', 'wc-ratesync' ),
		];

		$settings[] = [ 'type' => 'sectionend', 'id' => 'ratesync_options' ];

		return $settings;
	}

	/**
	 * Did the user activate their RateSync license?
	 *
	 * @since 0.0.1
	 *
	 * @return bool
	 */
	private function license_active() {
		return 'active' === get_option( 'ratesync_license_status', 'inactive' );
	}

	/**
	 * Get a list of states excl. Armed Forces states and U.S. territories.
	 *
	 * @since 1.1.0
	 *
	 * @return array List of U.S. states.
	 */
	private function get_states() {
		$all_states = WC()->countries->get_states( 'US' );
		$disabled   = [ 'AA', 'AE', 'AP', 'AS', 'GU', 'MP', 'PR', 'UM', 'VI' ];
		$states     = array_diff_key( $all_states, array_fill_keys( $disabled, '' ) );

		foreach ( $states as $abbrev => $name ) {
			$states[ $abbrev ] = [
				'abbrev'           => $abbrev,
				'name'             => $name,
				'shipping_taxable' => WC_RS_States::is_shipping_taxable( $abbrev ),
			];
		}

		return $states;
	}

	/**
	 * Output tax states field.
	 *
	 * @since 0.0.1
	 *
	 * @param $value Field values.
	 */
	public function output_tax_states( $value ) {
		$description = WC_Admin_Settings::get_field_description( $value );
		$tax_states  = wc_rs_get_tax_states();

		wp_enqueue_script( 'wc-rs-tax-states' );

		wp_localize_script( 'wc-rs-tax-states', 'taxStatesLocalizeScript', array(
			'tax_states' => $tax_states,
			'all_states' => $this->get_states(),
			'strings'    => [
				'yes'              => __( 'Yes', 'wc-ratesync' ),
				'no'               => __( 'No', 'wc-ratesync' ),
				'blank_state_text' => __( 'You can collect tax in any number of states. Click the button below to get started.', 'wc-ratesync' ),
				'delete'           => __( 'Delete', 'wc-ratesync' ),
				'add_tax_states'   => __( 'Add tax states', 'wc-ratesync' ),
				'close_modal'      => __( 'Close modal panel', 'wc-ratesync' ),
				'choose_states'    => __( 'Choose states&hellip;', 'wc-ratesync' ),
				'state'            => __( 'State', 'wc-ratesync' ),
				'select_all'       => __( 'Select all', 'wc-ratesync' ),
				'select_none'      => __( 'Select none', 'wc-ratesync' ),
				'add'              => __( 'Add', 'wc-ratesync' ),
			]
		) );

		include RateSync()->plugin_path() . '/includes/views/html-tax-states-table.php';
	}

	/**
	 * Get sync status formatted for display.
	 *
	 * @since 0.0.1
	 *
	 * @return string
	 */
	protected function get_sync_status() {
		return ucfirst( str_replace( '_', ' ', get_option( 'ratesync_sync_status' ) ) );
	}

	/**
	 * Output sync status field.
	 *
	 * @since 0.0.1
	 *
	 * @param $value Field values.
	 */
	public function output_sync_status( $value ) {
		$description = WC_Admin_Settings::get_field_description( $value );
		$last_sync   = WC_Admin_Settings::get_option( $value['id'], '' );

		if ( empty( $last_sync ) ) {
			$message = __( 'Not synced yet.', 'wc-ratesync' );
		} else {
			$message = date( 'F d, Y H:i:s T', $last_sync ) . ' (' . $this->get_sync_status() . ')';
		}

		?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                <?php echo $description['tooltip_html']; ?>
            </th>
            <td class="forminp">
                <span><?php echo $message; ?></span>
            </td>
        </tr>
        <?php
	}

	/**
	 * Output license key field.
	 *
	 * @since 0.0.1
	 *
	 * @param $value Field values.
	 */
	public function output_license_key( $value ) {
		// Handle custom attributes
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		// Handle description
		$description = WC_Admin_Settings::get_field_description( $value );

		// Output field HTML
		$option_value   = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		$field_type     = $this->license_active() ? 'hidden' : 'text';
		$deactivate_url = wp_nonce_url( add_query_arg( 'wc_rs_deactivate', true ), 'wc_rs_deactivate' );

		?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                <?php echo $description['tooltip_html']; ?>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                <?php if ( $this->license_active() ): ?>
                    <span class="wc_rs_license_key"><?php echo $option_value; ?></span>
                    <a class="button wc_rs_deactivate"
                       href="<?php echo esc_url( $deactivate_url ); ?>"><?php _e( 'Deactivate', 'wc-ratesync' ); ?></a>
                <?php endif; ?>
                <input
                        name="<?php echo esc_attr( $value['id'] ); ?>"
                        id="<?php echo esc_attr( $value['id'] ); ?>"
                        type="<?php echo $field_type; ?>"
                        style="<?php echo esc_attr( $value['css'] ); ?>"
                        value="<?php echo esc_attr( $option_value ); ?>"
                        class="<?php echo esc_attr( $value['class'] ); ?>"
                        placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                    <?php echo implode( ' ', $custom_attributes ); ?>
                /> <?php echo $description['description']; ?>
            </td>
        </tr>
        <?php
	}

	/**
	 * Sanitize and save tax states.
	 *
	 * @since 0.0.1
	 */
	protected function save_tax_states() {
		if ( ! isset( $_POST['ratesync_tax_states'] ) ) {
			$states = [];
		} else {
			$states = $_POST['ratesync_tax_states'];
		}
		wc_rs_set_tax_states( $states );
	}

	/**
	 * When the user saves their license key, activate it if it isn't active
	 * already.
	 *
	 * @since 0.0.1
	 */
	protected function maybe_activate_license() {
		$license = trim( strval( $_POST['ratesync_license_key'] ) );

		if ( ! empty( $license ) && ! $this->license_active() ) {

			// Send API request to activate license
			$api_params = [
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_id'    => RS_SL_ITEM_ID,
				'url'        => home_url()
			];

			$response = wp_remote_post( RS_SL_STORE_URL, [
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			] );

			// Check response and update license status accordingly
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if ( true === $license_data->success ) {
					update_option( 'ratesync_license_status', 'active' );
				}
			}

			// Show admin notice indicating failure or success
			if ( $this->license_active() ) {
				WC_Admin_Settings::add_message( __( 'RateSync license activated successfully.', 'wc-ratesync' ) );
			} else {
				WC_Admin_Settings::add_error( __( 'Failed to activate RateSync license. Please check your license key and try again', 'wc-ratesync' ) );
			}
		}
	}

	/**
	 * Runs when the user saves their WooCommerce tax options.
	 *
	 * Triggers a rate sync so long as the user has entered a valid
	 * license key.
	 *
	 * @since 0.0.1
	 */
	protected function sync_rates() {
		global $wpdb;

		$tax_states = wc_rs_get_tax_states( true );
		$license    = WC_Admin_Settings::get_option( 'ratesync_license_key' );

		// Delete orphaned tax rates
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$wpdb->prefix}woocommerce_tax_rates
			WHERE tax_rate_state NOT IN (%s);
		", implode( ',', $tax_states ) ) );

		// If license entered, start sync
		if ( ! empty( $license ) ) {
			$sync = new WC_RS_Sync();
			$sync->start();

			WC_Admin_Settings::add_message( __( 'Tax rate sync started.', 'wc-ratesync' ) );
		}
	}

	/**
	 * Process request to deactivate license key.
	 *
	 * @since 0.0.1
	 */
	public function maybe_deactivate_license() {
		if ( isset( $_REQUEST['wc_rs_deactivate'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc_rs_deactivate' ) ) {

			$license = trim( get_option( 'ratesync_license_key' ) );

			// Send API request to deactivate license
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_id'    => RS_SL_ITEM_ID,
				'url'        => home_url()
			);

			$response = wp_remote_post( RS_SL_STORE_URL, [
				'body'      => $api_params,
				'timeout'   => 15,
				'sslverify' => false
			] );

			// Check response and update license status
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if ( true === $license_data->success ) {
					update_option( 'ratesync_license_status', 'inactive' );
				}
			}

			// Show admin notice indicating failure or success
			if ( ! $this->license_active() ) {
				WC_Admin_Settings::add_message( __( 'RateSync license deactivated successfully.', 'wc-ratesync' ) );
			} else {
				WC_Admin_Settings::add_error( __( 'Failed to deactivate RateSync license. Please wait a minute and try again.', 'wc-ratesync' ) );
			}
		}
	}

	/**
	 * Runs after tax options are saved. Does the following:
	 *
	 *   1) Sanitizes and saves the tax states option
	 *   2) Activates the entered license key if necessary
	 *   3) Triggers a rate sync
	 *
	 * @since 0.0.1
	 */
	public function save_settings() {
		self::save_tax_states();
		self::maybe_activate_license();
		self::sync_rates();
	}

}

new WC_RS_Settings();