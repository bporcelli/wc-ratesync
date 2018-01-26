<?php

/**
 * RateSync functions.
 *
 * @author  WC RateSync
 * @package WC_RateSync
 */

/**
 * Get the user's tax states.
 *
 * @since 1.1.0
 *
 * @param  bool $abbrevs Return only the abbreviation for each state. 
 * @return array Tax states selected by the user.
 */
function wc_rs_get_tax_states( $abbrevs = false ) {
    $states = get_option( 'ratesync_tax_states' );

    if ( false === $states ) {
        return array();
    }

    $states = json_decode( $states, true );

    if ( $abbrevs ) {
        return wp_list_pluck( $states, 'abbrev' );
    } else {
        return $states;
    }
}

/**
 * Set the user's tax states.
 *
 * JSON encode before saving to avoid an invalid call to stripslashes in
 * WC_Admin_Settings::get_option.
 *
 * @since 1.1.0
 *
 * @param array $tax_states New tax states for user.
 */
function wc_rs_set_tax_states( $tax_states ) {
    update_option( 'ratesync_tax_states', json_encode( $tax_states ) );
}
