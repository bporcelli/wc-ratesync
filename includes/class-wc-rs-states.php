<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * RateSync states.
 *
 * Provides access to state data.
 *
 * @package WC_RateSync
 * @author  Brett Porcelli
 */
class WC_RS_States {

    /**
     * @var array Loaded state data.
     */
    private static $states;

    /**
     * Get all states.
     *
     * @since 1.1.0
     *
     * @return array
     */
    public static function get_states() {
        if ( empty( self::$states ) ) {
            self::$states = require RateSync()->plugin_path() . '/data/states.php';
        }
        return self::$states;
    }

    /**
     * Get a single state.
     *
     * @since 1.1.0
     *
     * @param  string $abbrev State abbreviation.
     * @return array State data.
     */
    public static function get_state( $abbrev ) {
        $states = self::get_states();

        if ( array_key_exists( $abbrev, $states ) ) {
            return $states[ $abbrev ];
        } else {
            return array();
        }
    }

    /**
     * Determine whether shipping is taxable in a state.
     *
     * @since 1.1.0
     *
     * @param  string $abbrev State abbreviation.
     * @return string 'yes' if shipping is taxable, otherwise 'no.'
     */
    public static function is_shipping_taxable( $abbrev ) {
        $states = self::get_states();

        if ( array_key_exists( $abbrev, $states ) ) {
            return $states[ $abbrev ][ 'shipping_taxable' ];
        } else {
            return 'no';
        }
    }
}
