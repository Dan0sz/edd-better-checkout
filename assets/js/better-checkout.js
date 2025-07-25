/**
 * EDD Better Checkout for FFW.Press
 *
 * @author Daan van den Bergh
 * @url    https://ffw.press
 */
var edd_global_vars;

jQuery(document).ready(function ($) {
    var ffwp_checkout = {
        init: function () {
            $(document.body).on('edd_gateway_loaded', this.add_vat_id_tooltip);
            $(document.body).on('edd_taxes_recalculated', this.add_class);

            /**
             * Events after which the shopping cart is refreshed.
             */
            $(document).on('edd_cart_billing_address_updated', this.set_loader_cart);
            $(document).on('edd_eu_vat:before_vat_check', this.set_loader_cart);
            $('#billing_country').on('change', this.set_loader_cart);
            $('.edd-apply-discount').on('click', this.set_loader_cart);
        },

        add_vat_id_tooltip: function () {
            let label = document.querySelector('#edd-card-vat-wrap .edd-label');

            if (label === null) {
                return;
            }

            let tooltip = document.createElement('span');
            tooltip.className = 'daan-dev-tooltip';
            tooltip.innerHTML = '?';

            let tooltip_text = document.createElement('span');
            tooltip_text.className = 'daan-dev-tooltip-text';
            tooltip_text.innerHTML = 'If you want your business\' information to appear on your invoice, please enter a valid EU VAT ID here.';

            tooltip.appendChild(tooltip_text);
            label.appendChild(tooltip);
        },

        add_class: function () {
            var $result_data = $('#edd-vat-check-result').data('valid');
            var $validate_button = $('#edd-vat-check-button');

            if ($result_data === 1) {
                $validate_button.addClass('daan-dev-vat-valid');
                $validate_button.val('Valid');
            } else {
                $validate_button.removeClass('daan-dev-vat-valid');
                $validate_button.val('Validate');
            }
        },

        set_loader_cart: function () {
            var $cart = $('#edd_checkout_cart');

            $cart.append('<div class="daan-dev-loader edd-loading-ajax edd-loading"></div>');
            $cart.css({
                opacity: 0.5
            });
        }
    };

    ffwp_checkout.init();
});