jQuery(function ($) {

    var shipping_meta_box = $('#yith-shippo-order-shippings');
    shipping_meta_box.addClass('not-sortable');
    $(".meta-box-sortables").sortable({
        items: ':not(.not-sortable)'
    });
    shipping_meta_box.find('.postbox-header h2').off('click');

    $(document).on('click', '.yith-shippo-shipping-section-box--title,.yith-shippo-shipping-section-box--toggle', function (e) {
        e.stopImmediatePropagation();
        var toggle = $(e.target),
            section = toggle.closest('.yith-shippo-shipping-section-box'),
            content = section.find('.yith-shippo-shipping-section-box--content');

        if (section.is('.yith-shippo-shipping-section-box--closed')) {
            content.slideDown(400);
        } else {
            content.css({display: 'block'});
            content.slideUp(400);
        }

        section.toggleClass('yith-shippo-shipping-section-box--closed');

    });

    if ($('#yith-shippo-order-meta-box').hasClass('yith-shippo-hide-shipping-row')) {
        var item_id = $('#yith-shippo-order-meta-box').data('order_item_id');

        $('#order_shipping_line_items').find('tr.shipping[data-order_item_id="' + item_id + '"]').hide();
    }


    var error_modal = function (title, message) {

            yith.ui.confirm(
                {
                    title: title,
                    message: message,
                    confirmButtonType: 'confirm',
                    confirmButton: yith_shippo_order_meta_box_params.errorButtonLabel,
                    width: 550,
                    closeAfterConfirm: true,
                    classes: {
                        cancel: 'yith-shippo-hide-cancel-button',
                    }
                }
            );
        },
        meta_box_configuration = function () {
            var order_configuration = $('#yith-shippo-order-meta-box').data('order_configuration'),
                items_configuration = $('#yith-shippo-order-meta-box').data('items'),
                shipping_list = $('.yith-shippo-order-shipping-list'),
                total_qty = 0,
                total_items_added = 0,
                total_amount = 0,
                global_total_cost = 0,
                items_added = {};


            $.each(order_configuration, function (i, value) {
                total_qty = total_qty + parseInt(value.max_qty);
                global_total_cost = global_total_cost + parseInt(value.max_qty) * parseFloat(value.cost);

            });
            $.each(items_configuration, function (rate_key, products) {
                $.each(products, function (product_id, config) {
                    total_amount += parseFloat(config.value);

                    if (typeof items_added[product_id] === 'undefined') {
                        items_added[product_id] = parseInt(config.qty);
                        total_items_added++;
                    } else {
                        items_added[product_id] += parseInt(config.qty);
                    }
                });
            });

            var products_in_list = {
                template: wp.template('yith-shippo-product-list'),
                dom: {
                    addNew: $('.yith_shippo_new_product'),
                },
                _init_product_row: function () {
                    var rows = $('.yith-shippo-product-shipping--row');

                    rows.each(function () {
                        var current_row = $(this);
                        products_in_list.init_single_product_row(current_row);
                    });
                },
                init_single_product_row: function (current_row) {
                    var qty_field = current_row.find('input.yith-product-qty'),
                        product_id = current_row.find('select.yith-shippo-product-list').val(),
                        value_field = current_row.find('input.yith-product-price'),
                        shipping_box = current_row.parents('.yith-shippo-shipping-section-box'),
                        global_conf = order_configuration[product_id];

                    qty_field.attr('disabled', false);

                    if ('all' !== product_id) {
                        var single_conf = items_configuration[shipping_box.data('rate-key')];
                        qty_field.attr('max', global_conf.max_qty);
                        if (typeof single_conf !== 'undefined' && typeof single_conf[product_id] !== 'undefined') {
                            qty_field.val(single_conf[product_id].qty);
                            value_field.val(single_conf[product_id].value);
                        } else {
                            qty_field.val(global_conf.max_qty);
                            value_field.val(parseFloat(global_conf.cost) * global_conf.max_qty);
                        }
                    } else {

                        qty_field.attr('max', total_qty);
                        value_field.val(global_total_cost);
                        qty_field.val(total_qty).attr('disabled', true);
                    }
                },
                init: function () {
                    this._init_product_row();
                    this.dom.addNew.on('click', this.addNewRule);
                    $(document).on('click', '.yith-shippo-remove-rule', this.removeRule);
                    $(document).on('change', 'select.yith-shippo-product-list', this.handleProductSelectChange);


                    $('select.yith-shippo-product-list').each(function () {
                        products_in_list.handleProductSelectChange.call($(this));
                    });
                },
                getSelectedItems: function (list) {
                    var rows = list.find('.yith-shippo-product-shipping--row'),
                        selectedItems = [];
                    rows.each(function () {
                        var current_row = $(this),
                            product_id = current_row.find('select.yith-shippo-product-list').val();
                        if ('all' !== product_id) {
                            selectedItems.push(product_id);
                        }
                    });


                    return selectedItems;
                },
                processNewProductList: function (newRow, selected_items) {

                    newRow.find('select.yith-shippo-product-list option').attr('disabled', false);
                    $.each(selected_items, function (i, value) {
                        newRow.find('select.yith-shippo-product-list option[value="' + value + '"]').attr('disabled', true);
                    });
                    if (newRow.find('select.yith-shippo-product-list option:not([disabled])').length) {
                        newRow.find('select.yith-shippo-product-list option:selected').attr('selected', false);
                        newRow.find('select.yith-shippo-product-list option:not([disabled]):first').attr('selected', true);
                    } else {
                        newRow = false;
                    }
                    return newRow;
                },
                addNewRule: function (e) {
                    e.preventDefault();
                    var target = $(e.target),
                        ruleListContainer = target.parents('.yith-plugin-fw-product-list-field-wrapper'),
                        ruleList = ruleListContainer.find('.yith-shippo-producs-shipping-list'),
                        allowed_items = products_in_list.getSelectedItems(ruleList),
                        dataList =ruleList.find('.yith-shippo-product-shipping--row').map(function() {
                        return parseInt($(this).attr("data-subindex"));
                    }).get();

                    var newIndex = Math.max.apply(null, dataList)+1,
                        newRow = $(products_in_list.template({index: newIndex}));

                    newRow = products_in_list.processNewProductList(newRow, allowed_items);
                    if (newRow) {
                        ruleList.append(newRow);
                        $(document.body).trigger('wc-enhanced-select-init');
                        $(document).trigger('yith_fields_init');

                        products_in_list.handleProductSelectChange.call(newRow.find('select.yith-shippo-product-list'));
                    }
                    if ( false === newRow || 1 === newRow.find('select.yith-shippo-product-list option:not([disabled])').length) {
                        ruleListContainer.find('.yith-shippo-new-product').hide();
                    }

                },
                removeRule: function (ev) {
                    ev.preventDefault();
                    var row = $(this).parents('.yith-shippo-product-shipping--row');
                    $(this).parents('.yith-plugin-fw-product-list-field-wrapper').find('.yith-shippo-new-product').show();
                    row.remove();

                },
                handleProductSelectChange: function (e) {
                    var target = false;

                    if (typeof e !== 'undefined') {
                        target = $(e.target);
                    } else if ($(this).length && $(this).hasClass('yith-shippo-product-list')) {
                        target = $(this);
                    }
                    var row = target.parents('.yith-shippo-product-shipping--row'),
                        current_val = target.val();

                    if ('all' === current_val) {
                        row.parent().parent().find('.yith-shippo-new-product').hide();
                        row.parent().parent().find('.yith-product-qty').hide();
                        row.siblings().remove();
                    }else{
                        var option_available_length = target.find('option[value!="all"]').length,
                            total_rows = row.parent().find('.yith-shippo-product-shipping--row').length;

                       if( option_available_length !== total_rows  ) {
                           row.parent().parent().find('.yith-shippo-new-product').show();
                       }
                        row.parent().parent().find('.yith-product-qty').show();
                    }
                    products_in_list.init_single_product_row(row);
                },
            };
            products_in_list.init();
            var service_popups = {},
                modal = null,
                show_modal = function (popup_key, old_selected) {
                    var content_pop = $(service_popups[popup_key]);
                    if ('' !== old_selected) {
                        content_pop.find('.yith-shippo-service-popup-row').removeClass('service-checked');
                        content_pop.find('.yith-shippo-service-popup-row[data-service-id="' + old_selected + '"]').addClass('service-checked');
                    }
                    modal = yith.ui.modal(
                        {
                            title: yith_shippo_order_meta_box_params.servicePopUp.title,
                            content: content_pop,
                            closeWhenClickingOnOverlay: true,
                            allowClosingWithEsc: true,
                            showClose: true,
                            width: 550,
                            allowWpMenu: false,
                            allowWpMenuInMobile: false,
                        }
                    );
                },
                calculate_total_weight = function (parcel) {
                    var ship_box = parcel.parent(),
                        product_list = ship_box.find('.yith-shippo-producs-shipping-list .yith-shippo-product-shipping--row'),
                        total_weight = 0;

                    product_list.each(function (i, product_row) {
                        var product_id = $(product_row).find('select.yith-shippo-product-list').val(),
                            qty = $(product_row).find('input.yith-product-qty').val(),
                            weight = 0;
                        if (typeof order_configuration[product_id] != 'undefined') {
                            weight = order_configuration[product_id].weight;
                        }

                        total_weight += parseFloat(weight) * parseInt(qty);

                    });
                    return total_weight;
                };
            $(document).on('click', '.yith-shippo-edit-service,.yith-shippo-choose-service', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var single_shipping = $(this).parents('.yith-shippo-shipping-section-box'),
                    metabox = $('#yith-shippo-order-shippings'),
                    rate_key = single_shipping.find('.yith_shippo_rate_key').val(),
                    blockArgs = {
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        },
                    };
                metabox.block(blockArgs);
                if (typeof service_popups[rate_key] === 'undefined' || '' === service_popups[rate_key]) {

                    var fields = $(':input', single_shipping).serializeJSON(),
                        metabox_index = 'yith-shippo-shipping-metabox',
                        args = {};


                    $.each(fields[metabox_index], function (key, element) {
                        $.each(element, function (index, value) {
                                args[index] = value;
                            }
                        );
                    });
                    args['order_id'] =  $('#yith-shippo-order-meta-box').data('order_id');
                    args['rate_key'] = rate_key;
                    var data = {
                        action: yith_shippo_order_meta_box_params.actions.findService,
                        security: yith_shippo_order_meta_box_params.nonces.findService,
                        rate: args
                    };


                    $.ajax({

                        type: 'POST',
                        url: yith_shippo_order_meta_box_params.ajaxurl,
                        data: data,
                        dataType: 'json',
                        success: function (response) {
                            metabox.unblock();
                            if (response.success && typeof response.data !== 'undefined') {
                                single_shipping.parent().unblock();
                                service_popups[rate_key] = response.data;
                                show_modal(rate_key, '');
                            } else {
                                var message = '';
                                $.each(response.data, function (i, value) {
                                    message += '<li>' + value.message + '</li>';
                                });

                                message = '<ul>' + message + '</ul>';

                                error_modal(yith_shippo_order_meta_box_params.servicePopUp.title_error, message);
                            }

                        }
                    });
                } else {
                    metabox.unblock();
                    var old_service_selected = single_shipping.find('.yith-shippo-service-id').val();
                    show_modal(rate_key, old_service_selected);
                }
            });
            $(document).on('click', '.yith-shippo-service-popup-row', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var order_id = $('#yith-shippo-order-meta-box').data('order_id'),
                    service_selected = $(this),
                    rate_key = service_selected.data('rate-key'),
                    rate = jQuery.parseJSON(service_selected.data('rate')),
                    parcel = service_selected.parent().data('parcel'),
                    blockArgs = {
                        message: '',
                        overlayCSS: {backgroundColor: '#FFFFFF', opacity: 0.8, cursor: 'wait'},
                    },
                    shipping_container = $(document).find('#yith-shippo-order-meta-box'),
                    shipping_row = shipping_container.find('.yith-shippo-shipping-section-box[data-rate-key="' + rate_key + '"]');
                shipping_container.block(blockArgs);

                var data = {
                    action: yith_shippo_order_meta_box_params.actions.updateRate,
                    security: yith_shippo_order_meta_box_params.nonces.updateRate,
                    rate: rate,
                    parcel: parcel,
                    rate_key: rate_key,
                    order_id: order_id
                };

                $.ajax({

                    type: 'POST',
                    url: yith_shippo_order_meta_box_params.ajaxurl,
                    data: data,
                    dataType: 'json',
                    success: function (response) {

                        if (typeof response.updated !== 'undefined' && response.updated) {
                            var img_url = service_selected.find('img').attr('src'),
                                service_name = service_selected.find('.yith-shippo-service-popup--name').html(),
                                service_price = service_selected.find('.yith-shippo-service-popup--price').html(),
                                service_days = '';
                            if (service_selected.find('.yith-shippo-service-popup--days').length) {
                                service_days = service_selected.find('.yith-shippo-service-popup--days').html();
                            }

                            var the_service = shipping_row.find('.yith-shippo-choose-service-wrap');

                            if (the_service.find('img').length) {
                                the_service.find('img').attr('src', img_url);
                            } else {
                                var img = $('<img>');

                                img.attr('src', img_url);
                                the_service.find('.yith-shippo-service-image').append(img);
                            }
                            the_service.find('.yith-shippo-service-label .yith-shippo-service-label-first').html(service_name);
                            the_service.find('.yith-shippo-service-label .yith-shippo-service-label-price strong').html(service_price);
                            the_service.find('.yith-shippo-service-days').html(service_days);
                            the_service.find('.yith-shippo-service-id').val(rate.service);
                            if (service_days === '') {
                                the_service.find('.yith-shippo-service-days').hide();
                            } else {
                                the_service.find('.yith-shippo-service-days').show();
                            }


                            the_service.find('.yith-shippo-service-content').removeClass('hide');
                            the_service.find('.yith-shippo-service-empty-state-wrapper').addClass('hide');
                        }
                    },
                    complete: function () {
                        if (typeof modal !== null) {
                            modal.close();
                        }
                        shipping_container.unblock();
                    }
                });

            });
            $(document).on('change', '.yith-shippo-shipping-section-box :input', function (e) {
                var shipping_row = $(this).parents('.yith-shippo-shipping-section-box'),
                    rate_key = shipping_row.data('rate-key');
                service_popups[rate_key] = '';
                shipping_row.find('.yith-shippo-service-id').val('');
                shipping_row.find('.yith-shippo-service-content').addClass('hide');
                shipping_row.find('.yith-shippo-service-empty-state-wrapper').removeClass('hide');
            });
            $(document).on('change', 'select.yith-shippo-package', function (e) {
                var parent = $(this).parents('.yith-shippo-parcel-info'),
                    parcels_info = parent.data('parcels'),
                    parcel_id = $(this).val();

                if (typeof parcels_info[parcel_id] !== 'undefined') {
                    parent.find('.yith-parcel-length').val(parcels_info[parcel_id].length);
                    parent.find('.yith-parcel-width').val(parcels_info[parcel_id].width);
                    parent.find('.yith-parcel-height').val(parcels_info[parcel_id].height);
                    parent.find('.yith-parcel-weight').val(parcels_info[parcel_id].weight);


                    var parcel_weight = '' === parcels_info[parcel_id].weight ? 1 : parseFloat(parcels_info[parcel_id].weight),
                        product_weight = calculate_total_weight(parent);

                    parent.find('.total_weight_value').html(parseFloat(parcel_weight) + product_weight);
                }
            }).trigger('change');
            $(document).on('click', '.yith-shippo-delete-shipping', function (e) {
                e.preventDefault();
                var ship_box = $(this).parents('.yith-shippo-shipping-section-box'),
                    key = ship_box.data('rate-key');

                var data = {
                        action: yith_shippo_order_meta_box_params.actions.deleteShipping,
                        security: yith_shippo_order_meta_box_params.nonces.deleteShipping,
                        order_id: $('#yith-shippo-order-meta-box').data('order_id'),
                        rate_key: key,
                    },
                    blockArgs = {
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        },
                    };

                yith.ui.confirm(
                    {
                        title: yith_shippo_order_meta_box_params.confirmDelete.title,
                        message: yith_shippo_order_meta_box_params.confirmDelete.message,
                        confirmButtonType: 'delete',
                        confirmButton: yith_shippo_order_meta_box_params.confirmDelete.confirmButton,
                        closeAfterConfirm: true,
                        onConfirm: function () {
                            $('#yith-shippo-order-meta-box').block(blockArgs);
                            $.ajax({

                                type: 'POST',
                                url: yith_shippo_order_meta_box_params.ajaxurl,
                                data: data,
                                dataType: 'json',
                                success: function (response) {
                                    $('#yith-shippo-order-meta-box').unblock();
                                    if (typeof response.deleted !== 'undefined' && response.deleted) {
                                        ship_box.remove();
                                    }
                                }
                            });
                        },
                    }
                );
            });
            $(document).on('click', '.yith-shippo-create-shipment', function (e) {
                e.preventDefault();
                var ship_box = $(this).parents('.yith-shippo-shipping-section-box'),
                    service = ship_box.find('.yith-shippo-service-id').val(),
                    key = ship_box.data('rate-key');

                if ('' === service) {
                    error_modal(yith_shippo_order_meta_box_params.errorconfirmShipment.title, yith_shippo_order_meta_box_params.errorconfirmShipment.message);

                } else {
                    var data = {
                            action: yith_shippo_order_meta_box_params.actions.createShipment,
                            security: yith_shippo_order_meta_box_params.nonces.createShipment,
                            order_id: $('#yith-shippo-order-meta-box').data('order_id'),
                            rate_key: key,
                        },
                        blockArgs = {
                            message: null,
                            overlayCSS: {
                                background: '#fff',
                                opacity: 0.6
                            },
                        };

                    yith.ui.confirm(
                        {
                            title: yith_shippo_order_meta_box_params.confirmShipment.title,
                            message: yith_shippo_order_meta_box_params.confirmShipment.message,
                            confirmButtonType: 'confirm',
                            confirmButton: yith_shippo_order_meta_box_params.confirmShipment.confirmButton,
                            closeAfterConfirm: true,
                            onConfirm: function () {
                                $('#yith-shippo-order-meta-box').block(blockArgs);
                                $.ajax({

                                    type: 'POST',
                                    url: yith_shippo_order_meta_box_params.ajaxurl,
                                    data: data,
                                    dataType: 'json',
                                    success: function (response) {
                                        $('#yith-shippo-order-meta-box').unblock();
                                        if (!response.success) {
                                            var message = '';
                                            $.each(response.data, function (i, value) {
                                                message += '<li>' + value.message + '</li>';
                                            });

                                            message = '<ul>' + message + '</ul>';
                                            error_modal(yith_shippo_order_meta_box_params.confirmShipment.title_error, message);
                                        } else {
                                            ship_box.find('.yith-shippo-shipping-section-box--content').html(response.data);
                                            $( document ).trigger( 'yith-plugin-fw-tips-init');
                                        }
                                    }
                                });
                            },
                        }
                    );
                }
            });
            $(document).on('click', '.yith-shippo-pay-shipping', function (e) {
                e.preventDefault();
                var ship_box = $(this).parents('.yith-shippo-shipping-section-box'),
                    key = ship_box.data('rate-key');

                var data = {
                        action: yith_shippo_order_meta_box_params.actions.payShipping,
                        security: yith_shippo_order_meta_box_params.nonces.payShipping,
                        order_id: $('#yith-shippo-order-meta-box').data('order_id'),
                        rate_key: key,
                    },
                    blockArgs = {
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        },
                    };

                yith.ui.confirm(
                    {
                        title: yith_shippo_order_meta_box_params.confirmPay.title,
                        message: yith_shippo_order_meta_box_params.confirmPay.message,
                        confirmButtonType: 'confirm',
                        confirmButton: yith_shippo_order_meta_box_params.confirmPay.confirmButton,
                        closeAfterConfirm: true,
                        onConfirm: function () {
                            $('#yith-shippo-order-meta-box').block(blockArgs);
                            $.ajax({

                                type: 'POST',
                                url: yith_shippo_order_meta_box_params.ajaxurl,
                                data: data,
                                dataType: 'json',
                                success: function (response) {
                                    $('#yith-shippo-order-meta-box').unblock();
                                    if (!response.success) {
                                        var message = '';
                                        $.each(response.data, function (i, value) {
                                            message += '<li>' + value.message + '</li>';
                                        });

                                        message = '<ul>' + message + '</ul>';

                                        error_modal(yith_shippo_order_meta_box_params.confirmPay.title_error, message);
                                    } else {
                                        ship_box.find('.yith-shippo-shipping-section-box--content').html(response.data);
                                    }
                                }
                            });
                        }
                    });
            });
        };

    $(document).on('click', '.yith-shippo-tracking--check-for-updates', function (e) {
        e.preventDefault();
        var ship_box = $(this).parents('.yith-shippo-shipping-section-box'),
            key = ship_box.data('rate-key');

        var data = {
                action: yith_shippo_order_meta_box_params.actions.updateTracking,
                security: yith_shippo_order_meta_box_params.nonces.updateTracking,
                order_id: $('#yith-shippo-order-meta-box').data('order_id'),
                rate_key: key,
            },
            blockArgs = {
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                },
            };
        ship_box.block(blockArgs);
        $.ajax({
            type: 'POST',
            url: yith_shippo_order_meta_box_params.ajaxurl,
            data: data,
            dataType: 'json',
            success: function (response) {
                $('#yith-shippo-order-meta-box').unblock();
                if (response.success) {
                    ship_box.find('.yith-shippo-shipping-section-box--content').html(response.data);
                    ship_box.unblock();
                }
            }
        });
    });
    $(document).on('click', '#yith-shippo-add-new-shipping', function (e) {
        e.preventDefault();
        var data = {
                action: yith_shippo_order_meta_box_params.actions.createShipping,
                security: yith_shippo_order_meta_box_params.nonces.createShipping,
                order_id: $('#yith-shippo-order-meta-box').data('order_id')
            },
            blockArgs = {
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                },
            };
        $('#yith-shippo-order-meta-box').block(blockArgs);
        $.ajax({

            type: 'POST',
            url: yith_shippo_order_meta_box_params.ajaxurl,
            data: data,
            dataType: 'json',
            success: function (response) {
                $('#yith-shippo-order-meta-box').unblock();
                if (response.success) {
                    $('#yith-shippo-order-meta-box').parent().html(response.data);
                    $(document.body).trigger('wc-enhanced-select-init');
                    $(document).trigger('yith_fields_init');
                    $(document).trigger('yith-shippo-metabox-init');
                } else {
                    var message = '';
                    $.each(response.data, function (i, value) {
                        message += '<li>' + value.message + '</li>';
                    });

                    message = '<ul>' + message + '</ul>';
                    error_modal(yith_shippo_order_meta_box_params.confirmShipment.title_error, message);
                }
            }
        });

    });


    $(document).on('yith-shippo-metabox-init', meta_box_configuration).trigger('yith-shippo-metabox-init');


    $(document).on('click', '.yith-shippo-tracking-history-view-detail', function (e) {
        e.preventDefault();
        var $t = $(this),
            history = $t.closest('.yith-shippo-shipping-tracking-history').find('.yith-shippo-tracking-history-list'),
            degrees = 0;
        if (history.hasClass('opened')) {
            history.removeClass('opened');
            degrees = 0;
        } else {
            history.addClass('opened');
            degrees = -180;
        }
        history.toggle('slow');
        $t.find('i').css({
            '-webkit-transform': 'rotate(' + degrees + 'deg)',
            '-moz-transform': 'rotate(' + degrees + 'deg)',
            '-ms-transform': 'rotate(' + degrees + 'deg)',
            'transform': 'rotate(' + degrees + 'deg)',
        });

    });
});