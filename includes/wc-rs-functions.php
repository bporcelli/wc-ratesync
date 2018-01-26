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

/**
 * Get the URL of a stylesheet or script.
 *
 * @since 1.1.0
 *
 * @param string $basename File basename.
 * @param string $type Asset type ('css' or 'js').
 * @param bool $minify If true, path to minified file is returned.
 */
function wc_rs_asset_url( $basename, $type, $minify = ! RS_DEBUG ) {
    $base_url = RateSync()->plugin_url() . '/assets';
    $suffix   = ( $minify ? '.min' : '' ) . '.' . $type;
    return $base_url . '/' . $type . '/' . $basename . $suffix;
}

/**
 * Register a stylesheet.
 *
 * @since 1.1.0
 *
 * @uses  wp_enqueue_style
 *
 * @param string $slug Stylesheet slug.
 * @param string $basename Stylesheet basename.
 * @param array $deps Dependencies.
 * @param string $ver Stylesheet version.
 */
function wc_rs_register_style( $slug, $basename, $deps = array(), $ver = false ) {
    wp_register_style( $slug, wc_rs_asset_url( $basename, 'css', false ), $deps, $ver );
}

/**
 * Enqueue a stylesheet.
 *
 * @since 1.1.0
 *
 * @uses  wp_enqueue_style
 *
 * @param string $slug Stylesheet slug.
 * @param string $basename Stylesheet basename.
 * @param array $deps Dependencies.
 * @param string $ver Stylesheet version.
 */
function wc_rs_enqueue_style( $slug, $basename, $deps = array(), $ver = false ) {
    wp_enqueue_style( $slug, wc_rs_asset_url( $basename, 'css', false ), $deps, $ver );
}

/**
 * Register a script.
 *
 * @since 1.1.0
 *
 * @uses  wp_register_script
 *
 * @param string $slug Script slug.
 * @param string $basename Script basename.
 * @param array $deps Dependencies.
 * @param string $ver Script version.
 */
function wc_rs_register_script( $slug, $basename, $deps = array(), $ver = false ) {
    wp_register_script( $slug, wc_rs_asset_url( $basename, 'js' ), $deps, $ver );
}

/**
 * Enqueue a script.
 *
 * @since 1.1.0
 *
 * @uses  wp_enqueue_script
 *
 * @param string $slug Script slug.
 * @param string $basename Script basename.
 * @param array $deps Dependencies.
 * @param string $ver Script version.
 */
function wc_rs_enqueue_script( $slug, $basename, $deps = array(), $ver = false ) {
    wp_enqueue_script( $slug, wc_rs_asset_url( $basename, 'js' ), $deps, $ver );
}
