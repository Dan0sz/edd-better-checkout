<?php
/**
 * @package Daan\EDDBetterCheckout
 * @author  Daan van den Bergh
 * @url https://daan.dev
 * @license MIT
 */

namespace Daan\EDD\BetterCheckout;

/**
 * Clearer checkout for customers who (try to) log in.
 *
 * When a customer clicks "Log in" at checkout, EDD replaces the email field with the login fields. If they then click
 * Complete Order without logging in (e.g. because the error of a failed login went unnoticed), there's no email address
 * to send along, and they get EDD's "Enter an email" error. So on the checkout a script:
 * - stops Complete Order while the login fields are shown, with a notice that says to log in first or check out as a
 *   guest,
 * - scrolls a failed login's error into view.
 * And "Enter an email" is rewritten for the cases that still get it.
 */
class CheckoutLogin {
    /**
     * EDD's original message.
     */
    const ORIGINAL_MESSAGE = 'Enter an email';

    /**
     * Build class.
     */
    public function __construct() {
        add_filter( 'gettext_easy-digital-downloads', [ $this, 'rewrite_message' ], 10, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ] );
    }

    /**
     * @param string $translation
     * @param string $text
     *
     * @return string
     */
    public function rewrite_message( $translation, $text ) {
        if ( $text !== self::ORIGINAL_MESSAGE ) {
            return $translation;
        }

        return __( 'We need your email address to complete your purchase. Please enter it, or log in to your account.', 'edd-better-checkout' );
    }

    /**
     * Load the script on the checkout.
     *
     * @return void
     */
    public function enqueue_script() {
        if ( ! function_exists( 'edd_is_checkout' ) || ! edd_is_checkout() ) {
            return;
        }

        $suffix = daan_load_better_checkout()->get_script_suffix();

        wp_enqueue_script(
                'daan-dev-better-checkout-login',
                EDD_BETTER_CHECKOUT_PLUGIN_URL . "assets/js/checkout-login$suffix.js",
                [],
                filemtime( EDD_BETTER_CHECKOUT_PLUGIN_DIR . "assets/js/checkout-login$suffix.js" ),
                true
        );
        wp_localize_script(
                'daan-dev-better-checkout-login',
                'daanCheckoutLogin',
                [
                        /* translators: %s: "check out as a guest" link. */
                        'message'   => __( "You're not logged in yet. Log in to your account above first, or %s.", 'edd-better-checkout' ),
                        'linkLabel' => __( 'check out as a guest', 'edd-better-checkout' ),
                ]
        );
    }
}
