jQuery(function ($) {
    /** Manage the single shipping rule */

    $('#yith_shippo_rule_fee_fee_type').on('change', function (e) {
        var value = $(this).val(),
            fixed = $('#yith_shippo_rule_fee').find('.fixed'),
            percentage = $('#yith_shippo_rule_fee').find('.percentage');

        if ('fixed' === value) {
            fixed.show();
            percentage.hide();
        } else {
            fixed.hide();
            percentage.show();
        }
    }).trigger('change');
    $('#yith_shippo_rule_service').on('change', function (e) {
        var value = $(this).val(),
            custom_label_checkbox = $('#yith_shippo_rule_label_enabled');

        if ('all' === value) {
            if (custom_label_checkbox.is(':checked')) {
                custom_label_checkbox.click();
            }
            custom_label_checkbox.parents('tr').hide();
        } else {
            custom_label_checkbox.parents('tr').show();
        }
    }).trigger('change');

    if ($('#yith_shippo_shipping_rule_table').length) {
        $(document).on('click', function () {
            window.onbeforeunload = null;
        });
    }
    $('.yith_toggle_rule input').on('change', function (e) {
        var tr = $(this).closest('tr'),
            table = tr.closest('table'),
            active = $(this).is(':checked'),
            rule_id = tr.find('th.check-column input').val(),
            data = {
                action: yith_shippo_shipping_rules_params.actions.toggleRuleStatus,
                rule_id: rule_id,
                rule_status: active,
                security: yith_shippo_shipping_rules_params.security,
            },
            blockArgs = {
                message: '',
                overlayCSS: {backgroundColor: '#FFFFFF', opacity: 0.8, cursor: 'wait'},
            };
        table.block(blockArgs);
        $.ajax(
            {
                type: 'POST',
                url: yith_shippo_shipping_rules_params.ajaxurl,
                data: data,
                dataType: 'json',
                success: function (response) {
                    table.unblock();
                }
            });
    });
    var shipping_rules = {
        template: wp.template('yith-shppo-new-condition-row'),
        dom: {
            rulesListContainer: $('#yith-shippo-subconditions-wrapper'),
            ruleList: false,
            addNew: $('#yith_shippo_new_condition'),
        },
        index: 0,
        _initParams: function () {
            this.dom.ruleList = this.dom.rulesListContainer.find('.yith-shippo-subcondition-list');
            this.index = this.dom.ruleList.find('.yith-shippo-subcondition-row').length || 0;
        },
        init: function () {
            this._initParams();
            this.dom.addNew.on('click', this.addNewRule);

            $(document).on('change', 'select.yith-shippo-subcondition-type', this.handleSubTypeChange);
            $(document).on('click', '.yith-shippo-remove-rule', this.removeRule);
            $('select.yith-shippo-subcondition-type').each(function () {
                shipping_rules.handleSubTypeChange.call($(this));
            });

        },
        nextIndex: function () {
            return ++this.index;
        },
        addNewRule: function (e) {
            e.preventDefault();
            var newIndex = shipping_rules.nextIndex(),
                newRow = $(shipping_rules.template({index: newIndex}));

            shipping_rules.dom.ruleList.append(newRow);
            $(document.body).trigger('wc-enhanced-select-init');
            $(document).trigger('yith_fields_init');
            shipping_rules.handleSubTypeChange.call(newRow.find('select.yith-shippo-subcondition-type'));

        },
        handleSubTypeChange: function (ev) {

            var subtype = $(this).val(),
                row = $(this).parents('.yith-shippo-subcondition-row'),
                ship_cost_mode = row.find('.yith_shippo_shipping_cost_mode').parents('.option-element'),
                ship_cost_value = row.find('.yith_shippo_shipping_cost_value').parents('.option-element'),
                ship_cost_currency = row.find('.yith_shippo_shipping_cost_currency').parents('.option-element'),
                prod_ids = row.find('.yith-post-search').parents('.option-element'),
                prod_cat_ids = row.find('.yith-term-search').parents('.option-element');


            if ('shipping_cost' === subtype) {
                ship_cost_mode.show();
                ship_cost_value.show();
                ship_cost_currency.show();
                prod_ids.hide();
                prod_cat_ids.hide();

            } else if ('product_ids' === subtype) {
                ship_cost_mode.hide();
                ship_cost_value.hide();
                ship_cost_currency.hide();
                prod_ids.show();
                prod_cat_ids.hide();
            } else {
                ship_cost_mode.hide();
                ship_cost_value.hide();
                ship_cost_currency.hide();
                prod_ids.hide();
                prod_cat_ids.show();
            }
        },
        removeRule: function (ev) {
            ev.preventDefault();
            var row = $(this).parents('.yith-shippo-subcondition-row').remove()
            row.remove();
        }
    };

    shipping_rules.init();

    $('#yith_shippo_shipping_rule_detail #main-save-button').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var required_field = $(this).closest('form').find('.yith-plugin-fw--required'),
            can_send = true,
            row = false;
        required_field.each(
            function () {
                var current_row = $(this);

                if (current_row.is(':visible')) {
                    if (current_row.hasClass('rule-condition')) {
                        var condition_list = current_row.find('.yith-shippo-subcondition-list .yith-shippo-subcondition-row');

                        condition_list.each(function () {
                            var current_condition = $(this),
                                field_to_check = current_condition.find('.yith-term-search,.yith-post-search').filter(':visible');
                            var selected = field_to_check.find(':selected');
                            if (selected.length === 0) {
                                can_send = false;
                                field_to_check.parent().find('.select2.select2-container').addClass('required');
                            }
                        });

                    } else if (current_row.hasClass('fee-charge')) {
                        if (!current_row.find('input').isNumeric()) {
                            can_send = false;
                            current_row.find('input').addClass('required');
                        }
                    } else if (current_row.hasClass('shipping-zones')) {
                        var field_to_check = $('.yith-shipping-zone-list'),
                            selected = field_to_check.find('.yith-plugin-fw-checkbox-array__row.selected');
                        if (selected.length === 0) {
                            can_send = false;
                            field_to_check.find('.yith-plugin-fw-checkbox-array__row span').addClass('required');
                        }
                    } else {
                        if ('' === current_row.find('input').val()) {
                            can_send = false;
                            current_row.find('input').addClass('required');
                        }
                    }
                    if (!can_send && !row) {
                        row = current_row;
                    }
                }
            }
        );

        if (can_send) {
            $(this).closest('form').submit();
        } else {
            row.scroll_to();
        }

    });
})
;

/** END SHIPPING RULE */