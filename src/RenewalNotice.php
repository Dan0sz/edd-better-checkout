<?php
/**
 * @package Daan\EDDBetterCheckout
 * @author  Daan van den Bergh
 * @url https://daan.dev
 * @license MIT
 */

namespace Daan\EDD\BetterCheckout;

/**
 * When a license with a subscription is renewed manually, EDD Recurring tells the customer at checkout that the
 * subscription will be replaced with one that "automatically renews Yearly for €49". This shows the date of the first
 * automatic renewal instead, e.g. "automatically renews on November 4, 2027 for €49".
 */
class RenewalNotice {
    /**
     * Build class.
     */
    public function __construct() {
        add_action( 'edd_purchase_form_before_submit', [ $this, 'replace_notice' ], 1 );
    }

    /**
     * Replace EDD Recurring's notice with ours.
     *
     * @return void
     */
    public function replace_notice() {
        if ( ! class_exists( '\EDD_Recurring' ) || ! \EDD_Recurring::$software_licensing ) {
            return;
        }

        $removed = remove_action( 'edd_purchase_form_before_submit', [ \EDD_Recurring::$software_licensing, 'checkout_license_renewal_details' ], 9 );

        if ( $removed ) {
            add_action( 'edd_purchase_form_before_submit', [ $this, 'render_notice' ], 9 );
        }
    }

    /**
     * EDD Recurring's checkout_license_renewal_details(), with the renewal date instead of the period.
     *
     * @return void
     */
    public function render_notice() {
        if ( ! edd_sl_renewals_allowed() || ! EDD()->session->get( 'edd_is_renewal' ) ) {
            return;
        }

        $renewals     = edd_sl_get_renewal_keys();
        $cart_details = EDD()->cart->get_contents_details();

        foreach ( edd_get_cart_contents() as $key => $item ) {
            if ( ! isset( $renewals[ $key ] ) || empty( $item['options']['is_renewal'] ) || empty( $item['options']['recurring'] ) ) {
                continue;
            }

            if ( ! get_post_meta( $item['id'], '_edd_sl_enabled', true ) ) {
                continue;
            }

            $license_id = edd_software_licensing()->get_license_by_key( $renewals[ $key ] );
            $license    = new \EDD\Recurring\Integrations\SoftwareLicensing\License( $license_id );
            $sub        = $license_id ? $license->get_subscription( [ 'status' => [ 'active', 'trialling', 'failing' ] ] ) : false;

            if ( ! $sub || ( $sub->is_active() && 'cancelled' === $sub->status ) ) {
                continue;
            }

            if ( ! array_key_exists( $key, $cart_details ) ) {
                return;
            }

            $subscription      = new \EDD\Recurring\Cart\Subscription( $cart_details );
            $recurring_amounts = $subscription->get_recurring_amount( $cart_details[ $key ] );
            $recurring_amount  = $recurring_amounts['amount'] ?? false;

            if ( ! $recurring_amount ) {
                return;
            }

            ?>
            <div id="edd-recurring-sl-cancel-replace" class="edd-alert edd-alert-warn">
                <p class="edd_error">
                    <?php
                    printf(
                        /* translators: 1: product name, 2: date of the first automatic renewal, 3: recurring amount */
                        esc_html__( 'Your existing subscription to %1$s will be cancelled and replaced with a new subscription that automatically renews on %2$s for %3$s.', 'edd-better-checkout' ),
                        esc_html( edd_get_download_name( $item['id'] ) ),
                        esc_html( date_i18n( get_option( 'date_format' ), $this->get_renewal_date( $sub, $item ) ) ),
                        '<span class="edd-recurring-sl-discount-amount-' . esc_attr( $key ) . '">' . esc_html( edd_currency_filter( edd_sanitize_amount( $recurring_amount ) ) ) . '</span>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * The date the new subscription renews for the first time, calculated like EDD Recurring does: one license length
     * after the current subscription's renewal date, or one period from now if that date has passed.
     *
     * @see \EDD\Recurring\Integrations\SoftwareLicensing\Upgrades::get_manual_renewal_expiration()
     * @see \EDD_Recurring_Subscriber::get_new_expiration()
     *
     * @param \EDD_Subscription $sub
     * @param array             $item
     *
     * @return int Timestamp.
     */
    private function get_renewal_date( $sub, $item ) {
        $now      = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
        $current  = strtotime( $sub->expiration );
        $price_id = $item['options']['price_id'] ?? null;

        if ( $current && $current >= $now ) {
            $download = new \EDD_SL_Download( $item['id'] );
            $unit     = $download->get_expiration_unit( $price_id ) ?: 'years';
            $length   = $download->get_expiration_length( $price_id ) ?: 1;

            return strtotime( '+' . $length . ' ' . $unit, $current );
        }

        switch ( $item['options']['recurring']['period'] ) {
            case 'quarter':
                $period = '+3 months';
                break;
            case 'semi-year':
                $period = '+6 months';
                break;
            default:
                $period = '+1 ' . $item['options']['recurring']['period'];
        }

        return strtotime( $period, $now );
    }
}
