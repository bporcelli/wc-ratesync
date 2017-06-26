<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * RateSync settings.
 *
 * Methods for rendering settings fields and saving settings.
 *
 * @author 	Brett Porcelli
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_stylesheet' ) );
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
		$tax_states     = WC_Admin_Settings::get_option( 'ratesync_tax_states', array() );
		$license_active = $this->license_active();

		if ( ! $license_active || empty( $tax_states ) ) {
			WC_RS_Notices::add( 'configure' );
		} else {
			WC_RS_Notices::remove( 'configure' );
		}
	}

	/**
	 * Enqueue the admin stylesheet.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_stylesheet() {
		wp_enqueue_style( 'wcrs', RateSync()->plugin_url() . '/assets/css/admin.css' );
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
	 * @return array
	 */
	public function add_settings( $settings ) {
		$settings[] = array( 'title' => __( 'RateSync options', 'wc-ratesync' ), 'type' => 'title','desc' => '', 'id' => 'ratesync_options' );
		
		$settings[] = array(
			'title'             => __( 'License key', 'wc-ratesync' ),
			'id'                => 'ratesync_license_key',
			'default'           => '',
			'placeholder'       => 'a12f35kasdc4673kdcbs215678',
			'type'              => 'rs_license',
			'desc_tip'          => __( 'Enter your RateSync license key. This was included in your download email.', 'wc-ratesync' ),
		);

		$settings[] = array(
			'title'    => __( 'Last sync', 'wc-ratesync' ),
			'id'       => 'ratesync_last_sync',
			'type'     => 'rs_sync_status',
			'desc_tip' => __( 'The time and status of the last rate sync. Rates will be synced once daily and when you save your tax settings.', 'wc-ratesync' ),
		);

		$settings[] = array(
			'title'       => __( 'Tax states', 'wc-ratesync' ),
			'id'          => 'ratesync_tax_states',
			'default'     => '',
			'type'        => 'rs_tax_states',
			'desc_tip'    => __( 'Select the states where you need to collect tax. If you are unsure about this, consult a tax professional.', 'wc-ratesync' ),
		);

		$settings[] = array( 'type' => 'sectionend', 'id' => 'ratesync_options' );

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
	 * Output tax states field.
	 *
	 * @since 0.0.1
	 *
	 * @param $value Field values.
	 */
	public function output_tax_states( $value ) {
		$description = WC_Admin_Settings::get_field_description( $value );
		extract( $description );

		$selections = WC_Admin_Settings::get_option( $value['id'], array() );

		// Get options; filter out Armed Forces states and U.S. territories
		$states   = WC()->countries->get_states( 'US' );
		$disabled = array( 'AA', 'AE', 'AP', 'AS', 'GU', 'MP', 'PR', 'UM', 'VI' );

		foreach ( $disabled as $abbrev ) {
			if ( isset( $states[ $abbrev ] ) ) {
				unset( $states[ $abbrev ] );
			}
		}

		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tooltip_html; ?>
			</th>
			<td class="forminp">
				<select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:350px" data-placeholder="<?php esc_attr_e( 'Choose states&hellip;', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'State', 'woocommerce' ) ?>" class="wc-enhanced-select">
					<?php
						if ( ! empty( $states ) ) {
							foreach ( $states as $key => $val ) {
								echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ) . '>' . $val . '</option>';
							}
						}
					?>
				</select> <?php echo ( $description ) ? $description : ''; ?> <br /><a class="select_all button" href="#"><?php _e( 'Select all', 'woocommerce' ); ?></a> <a class="select_none button" href="#"><?php _e( 'Select none', 'woocommerce' ); ?></a>
			</td>
		</tr><?php
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
		extract( $description );

		$last_sync = WC_Admin_Settings::get_option( $value['id'], '' );

		if ( empty( $last_sync ) ) {
			$message = __( 'Not synced yet.', 'wc-ratesync' );
		} else {
			$message = date( 'F d, Y H:i:s T', $last_sync ) . ' ('. $this->get_sync_status() . ')';
		}

		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tooltip_html; ?>
			</th>
			<td class="forminp">
				<span><?php echo $message; ?></span>
			</td>
		</tr><?php
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
		extract( $description );

		// Output field HTML
		$option_value   = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		$field_type     = $this->license_active() ? 'hidden' : 'text';
		$deactivate_url = wp_nonce_url( add_query_arg( 'wc_rs_deactivate', true ), 'wc_rs_deactivate' );

		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tooltip_html; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<?php if ( $this->license_active() ): ?>
				<span class="wc_rs_license_key"><?php echo $option_value; ?></span>
				<a class="button wc_rs_deactivate" href="<?php echo esc_url( $deactivate_url ); ?>"><?php _e( 'Deactivate', 'wc-ratesync' ); ?></a>
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
					/> <?php echo $description; ?>
			</td>
		</tr><?php
	}

	/**
	 * Sanitize and save tax states.
	 *
	 * @since 0.0.1
	 */
	protected function save_tax_states() {
		if ( ! isset( $_POST['ratesync_tax_states'] ) ) {
			$states = array();	
		} else {
			$states = $_POST['ratesync_tax_states'];
		}
		update_option( 'ratesync_tax_states', $states );
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
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_id'    => RS_SL_ITEM_ID,
				'url'        => home_url()
			);

			$response = wp_remote_post( RS_SL_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

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
	 * Starts a rate sync if the last sync was aborted OR the selected tax
	 * states have changed.
	 *
	 * @since 0.0.1
	 */
	protected function sync_rates() {
		global $wpdb;

		$tax_states = WC_Admin_Settings::get_option( 'ratesync_tax_states', array() );

		// Delete rates for removed states
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$wpdb->prefix}woocommerce_tax_rates
			WHERE tax_rate_state NOT IN (%s);
		", implode( ',', $tax_states ) ) );

		// Start sync
		$sync = new WC_RS_Sync();
		$sync->start();

		WC_Admin_Settings::add_message( __( 'Tax rate sync started.', 'wc-ratesync' ) );
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

			$response = wp_remote_post( RS_SL_STORE_URL, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => false ) );

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