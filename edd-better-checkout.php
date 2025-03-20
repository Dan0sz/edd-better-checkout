<?php
/**
 * Plugin Name: Easy Digital Downloads - Better Checkout
 * Description: Increase the UX of EDD's checkout by changing a few things.
 * Version: 1.0.0
 * Author: Daan from Daan.dev
 * Author URI: https://daan.dev
 * GitHub Plugin URI: Dan0sz/non-required-state-field
 * Primary Branch: master
 * License: MIT
 */

require_once __DIR__ . '/vendor/autoload.php';

$edd_better_checkout = new \Daan\EDD\BetterCheckout\Plugin();
