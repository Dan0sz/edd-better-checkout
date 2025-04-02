<?php
/**
 * @package Daan\EDDBetterCheckout
 * @author  Daan van den Bergh
 * @url https://daan.dev
 * @license MIT
 */

namespace Daan\EDD\BetterCheckout\Blocks;

use EDD\Blocks\Functions as Helpers;
use function EDD\Blocks\Checkout\get_cart_contents;
use function EDD\Blocks\Checkout\get_customer;
use function EDD\Blocks\Checkout\Forms\do_personal_info_forms;

class Checkout {
	/**
	 * Renders the entire EDD checkout block.
	 *
	 * This is a modified version of @see \EDD\Blocks\Checkout\checkout(). We just add a couple of action hooks, for easier modification.
	 *
	 * @since 2.0
	 *
	 * @param array $block_attributes The block attributes.
	 *
	 * @return string Checkout HTML.
	 */
	public function render( $block_attributes = [] ) {
		$block_attributes = wp_parse_args(
			$block_attributes,
			[
				'show_register_form' => edd_get_option( 'show_register_form' ),
			]
		);

		$classes = Helpers\get_block_classes(
			$block_attributes,
			[
				'wp-block-edd-checkout',
				'edd-blocks__checkout',
			]
		);

		$cart_items = get_cart_contents();

		if ( ! $cart_items && ! edd_cart_has_fees() ) {
			return '<p>' . edd_empty_cart_message() . '</p>';
		}

		if ( edd_item_quantities_enabled() ) {
			add_action( 'edd_cart_footer_buttons', 'edd_update_cart_button' );
		}

		// Check if the Save Cart button should be shown.
		if ( ! edd_is_cart_saving_disabled() ) {
			add_action( 'edd_cart_footer_buttons', 'edd_save_cart_button' );
		}

		ob_start();
		?>
        <div id="edd_checkout_form_wrap" class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>">
			<?php
			if ( is_user_logged_in() ) {
				$customer = get_customer();
				include EDD_BLOCKS_DIR . 'views/checkout/logged-in.php';
			}
			do_action( 'edd_before_checkout_cart' );

			include EDD_BLOCKS_DIR . 'views/checkout/cart/cart.php';

			do_action( 'edd_after_checkout_cart' );

			$this->do_purchase_form( $block_attributes );
			?>
        </div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Outputs the purchase form for checkout.
	 *
	 * This is a modified version of @see \EDD\Blocks\Checkout\Forms\do_purchase_form(). We just add a couple action hooks to allow for more modification.
	 *
	 * @since 2.0
	 *
	 * @param array $block_attributes The block attributes.
	 *
	 * @return void
	 */
	private function do_purchase_form( $block_attributes ) {
		$payment_mode = edd_get_chosen_gateway();
		$form_action  = edd_get_checkout_uri( 'payment-mode=' . $payment_mode );

		do_action( 'edd_before_purchase_form' );
		?>
        <form id="edd_purchase_form" class="edd_form edd-blocks-form edd-blocks-form__purchase" action="<?php echo esc_url( $form_action ); ?>" method="POST">
			<?php
			do_action( 'daan_edd_purchase_form_top' );

			do_personal_info_forms( $block_attributes );

			if ( edd_show_gateways() && edd_get_cart_total() > 0 ) {
				include EDD_BLOCKS_DIR . 'views/checkout/purchase-form/gateways.php';
			}

			if ( ! edd_show_gateways() ) {
				do_action( 'edd_purchase_form' );
			} else {
				?>
                <div id="edd_purchase_form_wrap"></div>
				<?php
			}
			?>
        </form>
		<?php
	}
}
