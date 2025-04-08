<?php
/**
 * Plugin Name: Easy Digital Downloads - Better Checkout
 * Description: Increase the UX of EDD's checkout by changing a few things.
 * Version: 1.0.1
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev
 * GitHub Plugin URI: Dan0sz/non-required-state-field
 * Primary Branch: master
 * License: MIT
 */

require_once __DIR__ . '/vendor/autoload.php';

define( 'EDD_BETTER_CHECKOUT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDD_BETTER_CHECKOUT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Filter EDD output.
 *
 * @return \Daan\EDD\BetterCheckout\Plugin|mixed
 */
function daan_load_better_checkout() {
	static $better_checkout;

	if ( $better_checkout === null ) {
		$better_checkout = new \Daan\EDD\BetterCheckout\Plugin();
	}

	return $better_checkout;
}

add_action( 'plugins_loaded', 'daan_load_better_checkout', 501 );
