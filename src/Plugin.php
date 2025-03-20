<?php
/**
 * @package Daan\EDDBetterCheckout
 * @author  Daan van den Bergh
 * @url https://daan.dev
 * @license MIT
 */

namespace Daan\EDD\BetterCheckout;

class Plugin {
	/**
	 * List of translateable texts that should be rewritten.
	 * Format: Rewritten text => Text to be translated.
	 */
	const DAAN_BETTER_CHECKOUT_REWRITE_TEXT_FIELDS = [
		'An account associated with this email address has already been registered. Please <a href="/account/">login</a> to complete your purchase. <a href="/wp-login.php?action=lostpassword">Lost your password</a>?'          => 'You must be logged in to purchase a subscription',
		'A pending order associated with this email address has been found. Please login to your account. If you don\'t have an account, please <a href="/wp-login.php?action=register">create an account</a> before proceeding.' => 'To complete this payment, please login to your account.',
		'The EU VAT Validation service seems to be down. Please try again in a few hours or deduct the calculated VAT from your next VAT declaration using the invoice you\'ll receive upon purchase.'                            => 'We\'re having trouble checking your VAT number. Please try again or contact our support team.',
		'The EU VAT Validation service seems to be overloaded at the moment. Please try again in a few seconds and keep trying. It\'ll work eventually.'                                                                          => 'We\'re having trouble checking your VAT number. The VIES service is unreachable.',
		'City'                                                                                                                                                                                                                    => 'Billing city',
		'Country'                                                                                                                                                                                                                 => 'Billing country',
		'Enter a valid VAT number (starting with a 2 letter country code) to reverse charge EU VAT.'                                                                                                                              => 'Enter the VAT number of your company.',
		'Name on Card'                                                                                                                                                                                                            => 'Name on the Card',
		'Payment'                                                                                                                                                                                                                 => 'Select Payment Method',
		''                                                                                                                                                                                                                        => 'Excluding %1$s&#37; tax',
		'State/Province'                                                                                                                                                                                                          => 'Billing state/Province',
		'Street + House No.'                                                                                                                                                                                                      => 'Billing address',
		'Suite, Apt No., PO Box, etc.'                                                                                                                                                                                            => 'Billing address line 2 (optional)',
		'Validate'                                                                                                                                                                                                                => 'Check',
		'Zip/Postal Code'                                                                                                                                                                                                         => 'Billing zip/Postal code',
	];

	/**
	 * @var string $plugin_text_domain
	 */
	private $plugin_text_domain = 'edd-better-checkout';

	/**
	 * @var array $gateways
	 */
	private $gateways = [];

	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action & Filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		/**
		 * Modify labels of checkout fields and error messages.
		 */
		add_filter( 'edd_checkout_personal_info_text', [ $this, 'return_your_details' ] );
		add_filter( 'gettext_easy-digital-downloads', [ $this, 'modify_text_fields' ], 1, 3 );
		add_filter( 'gettext_edd-eu-vat', [ $this, 'modify_text_fields' ], 1, 3 );
		add_filter( 'gettext_edds', [ $this, 'modify_text_fields' ], 1, 3 );
		add_filter( 'gettext_edd-recurring', [ $this, 'modify_text_fields' ], 1, 3 );

		/**
		 * When Taxes > 'Display Tax Rate' is enabled in EDD's settings, remove the mention for each
		 * shopping cart item, because it seems excessive.
		 */
		add_filter( 'edd_cart_item_tax_description', '__return_empty_string' );

		/**
		 * Discount related changes.
		 */
		add_filter( 'edd_fees_get_fees', [ $this, 'reword_negative_fee' ] );
		add_filter( 'edd_fees_get_fees', [ $this, 'remove_discount_for_existing_licenses' ] );

		// Modify required fields
		add_filter( 'edd_purchase_form_required_fields', [ $this, 'add_required_fields' ] );

		// Force available gateways
		// add_filter( 'edd_enabled_payment_gateways', [ $this, 'force_gateways' ], 10000, 1 );

		// Make sure VAT ID is in the correct format, i.e. contains a country code.
		add_action( 'edds_buy_now_checkout_error_checks', [ $this, 'validate_vat_id_format' ], 10, 2 );
		add_action( 'edd_checkout_error_checks', [ $this, 'validate_vat_id_format' ], 10, 2 );
	}

	/**
	 * Return 'Your Details'.
	 *
	 * @return string|null
	 */
	public function return_your_details() {
		return __( 'Your Details', $this->plugin_text_domain );
	}

	/**
	 * Modifies lines for a few input fields.
	 *
	 * @param mixed $translation
	 * @param mixed $text
	 * @param mixed $domain
	 *
	 * @return mixed
	 */
	public function modify_text_fields( $translation, $text, $domain ) {
		if ( in_array( $text, self::DAAN_BETTER_CHECKOUT_REWRITE_TEXT_FIELDS ) ) {
			return array_search( $text, self::DAAN_BETTER_CHECKOUT_REWRITE_TEXT_FIELDS );
		}

		return $translation;
	}

	/**
	 * Don't speak of a 'fee' if it's a negative fee, i.e. a discount.
	 *
	 * @param mixed $fees
	 *
	 * @return mixed
	 */
	public function reword_negative_fee( $fees ) {
		if ( empty( $fees ) ) {
			return $fees;
		}

		foreach ( $fees as &$fee ) {
			if ( (float) $fee[ 'amount' ] >= 0 ) {
				continue;
			}

			$fee[ 'label' ] = __( 'One-time Discount', $this->plugin_text_domain );
		}

		return $fees;
	}

	/**
	 * Discounts (i.e. negative fees) aren't allowed for renewals and/or upgrades.
	 * For some reason EDD Recurring and EDD Software Licensing don't play along
	 * nicely when it comes to do this, so this is the fix.
	 */
	public function remove_discount_for_existing_licenses( $fees ) {
		if ( empty( $fees ) ) {
			return $fees;
		}

		$cart         = EDD()->session->get( 'edd_cart' );
		$renewal_fees = [];

		foreach ( $cart as $item ) {
			$is_renewal = $item[ 'options' ][ 'is_renewal' ] ?? false;
			$is_upgrade = $item[ 'options' ][ 'is_upgrade' ] ?? false;

			if ( ! $is_renewal && ! $is_upgrade ) {
				continue;
			}

			$renewal_fees[ $item[ 'id' ] ] = $item[ 'options' ][ 'recurring' ][ 'signup_fee' ];
		}

		foreach ( $fees as $key => &$fee ) {
			/**
			 * This isn't a discount, so move on...
			 */
			if ( (float) $fee[ 'amount' ] >= 0 && $key != 'signup_fee' ) {
				continue;
			}

			foreach ( $renewal_fees as $renewal_fee ) {
				(float) $fee[ 'amount' ] -= (float) $renewal_fee;
			}

			if ( $fee[ 'amount' ] == 0 ) {
				unset( $fees[ $key ] );
			}
		}

		return $fees;
	}

	/**
	 * Add Last Name and Street + House No. as required field, because it's dumb not to ask that.
	 *
	 * @param mixed $required_fields
	 *
	 * @return mixed
	 */
	public function add_required_fields( $required_fields ) {
		if ( edd_cart_needs_tax_address_fields() && edd_get_cart_total() ) {
			$required_fields[ 'edd_last' ]     = [
				'error_id'      => 'invalid_last_name',
				'error_message' => 'Please enter your last name',
			];
			$required_fields[ 'card_address' ] = [
				'error_id'      => 'invalid_card_address',
				'error_message' => 'Please enter your Street + House no.',
			];
		}

		return $required_fields;
	}

	/**
	 * Somewhere all payment methods are lost. This functions forces them back.
	 *
	 * @param mixed $gateways
	 *
	 * @return mixed
	 */
	public function force_gateways( $gateways ) {
		if ( count( $gateways ) != count( $this->gateways ) && count( $this->gateways ) > 0 ) {
			return $this->gateways;
		}

		$this->gateways = $gateways;

		return $gateways;
	}

	/**
	 * In the future these should throw exceptions, existing `edd_set_error()` usage will be caught below.
	 *
	 * @param mixed $valid_data
	 * @param mixed $post
	 *
	 * @return void
	 */
	public function validate_vat_id_format( $valid_data, $post ) {
		$entered_vat_id = $post[ 'vat_number' ] ?? '';

		if ( ! $entered_vat_id ) {
			return;
		}

		$valid = (bool) preg_match( '/^[A-Z]{2}/', $entered_vat_id );

		if ( $valid ) {
			return;
		}

		edd_set_error(
			'invalid_vat_number',
			__(
				'The entered VAT ID isn\'t formatted correctly. Please add the country code at the beginning of the VAT ID, e.g. DE1234567890.',
				$this->plugin_text_domain
			)
		);
	}
}
