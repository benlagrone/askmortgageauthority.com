jQuery(
    function ($) {
        var modal = $('.yith-shippo-validator-modal'),
            modalOverlay = $('.yith-shippo-validator-modal--overlay'),
            is_error = false,
            block = function (element) {
                var blockArgs = {
                    message: '',
                    overlayCSS: {backgroundColor: '#FFFFFF', opacity: 0.8, cursor: 'wait'},
                };
                element.block(blockArgs);
            },
            unblock = function (element) {
                element.unblock();
            },
            openModal = function () {
                modalOverlay.fadeIn();
                modal.fadeIn();
            },
            closeModal = function () {
                modalOverlay.fadeOut();
                modal.fadeOut();
            },
            maybeCloseModal = function (e) {
                if ('keyup' === e.type && e.key === 'Escape' || 'click' === e.type && $(e.target).hasClass('yith-shippo-validator-modal--overlay')) {
                    closeModal();
                }
            },
            validateProducts = function (e) {

                var validateColumn = $(this).closest('div'),
                    offset_el = $('.yith-shippo-products-offset'),
                    offset_value = offset_el.data('offset'),
                    limit_value = offset_el.data('limit'),
                    post_data = {
                        action: yith_shippo_shipping_rates_params.actions.validateProducts,
                        security: yith_shippo_shipping_rates_params.security,
                        offset: offset_value,
                        limit: limit_value
                    };
                block(validateColumn);

                $.ajax(
                    {
                        type: 'POST',
                        dataType: 'json',
                        data: post_data,
                        url: yith_shippo_shipping_rates_params.ajaxurl,
                        success: function (response) {
                            if (response['success']) {
                                var wrongProducts = response.wrongProducts;
                                var countProducts = response.countProducts;

                                if ((typeof wrongProducts === 'undefined' || 0 === wrongProducts) && typeof e !== 'undefined' && !is_error) {
                                    modal.removeClass('yith-shippo-validator-modal__failed');
                                    modal.addClass('yith-shippo-validator-modal__success');
                                } else {
                                    is_error = true;
                                    modal.removeClass('yith-shippo-validator-modal__success');
                                    modal.addClass('yith-shippo-validator-modal__failed');
                                    if( countProducts > 3 ){
                                        modal.addClass('yith-shippo-validator-modal__more_products');
                                    }
                                    addWrongProducts(wrongProducts, countProducts);
                                    $('.yith-shippo-products-offset').data('offset', offset_value + limit_value);
                                }
                                openModal();
                            } else {
                                alert(response('error'));
                            }
                        },
                        complete: function () {
                            unblock(validateColumn);
                        },
                    }
                );

            },

            /**
             * Add the wrong product list into the invalid products element.
             *
             * @param wrongProducts
             */
            addWrongProducts = function (wrongProducts, countProducts) {
                var productList = $('.yith-shippo-invalid-products-list'),
                    productCount = $('.yith-shippo-product-count');

                $(productList).append(wrongProducts);

                productCount.html(countProducts);

            },

            validateProductsOnScroll = function (e) {
                var element = $(this).get(0);

                if (element.scrollTop + element.clientHeight + 1 > element.scrollHeight) {
                    validateProducts();
                }
            };

        $(document).on('click', '.yith-shippo-validate-products-weights-dimension', validateProducts);
        $('.yith-shippo-failed-validation').on('scroll', validateProductsOnScroll);
        $(document).on('click', '.yith-shippo-close, .yith-shippo-close-button', closeModal);
        $(document).on('click keyup', maybeCloseModal);

        $(document).on('change', '#yith-shippo-show-live-shipping-rates-cart-checkout', function (e) {
            var limit_rate = $('#yith-shippo-enable-limit-rate'),
                limit_cost = $('#yith-shippo-enable-limit-cost');

            if (!$(this).is(':checked')) {
                if (limit_rate.is(':checked')) {
                    limit_rate.click();
                }
                if (limit_cost.is(':checked')) {
                    limit_cost.click();
                }
            }
        });

    }
);
