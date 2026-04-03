/* global yith_shippo_parcel, yith */
jQuery(function($) {

      var parcels = {
        template: wp.template('yith-shippo-parcel'),
        parceList: $('#parcel-list'),
        mainContent: $('.yith-shippo-parcel-list'),
        shippoParcels: $('.yith-shippo-parcel-list').data('parcels'),
        modal: false,
        dynamicFields: ['width', 'length', 'height', 'name'],
        blockArgs: {
          message: '',
          overlayCSS: {backgroundColor: '#FFFFFF', opacity: 0.8, cursor: 'wait'},
        },
        selector: {
          addParcelButton: '.yith-shippo-add-parcel-box',
          checkAll: '.yith-shippo-bulk-action-check',
          applyBulk: '#yith_shippo_bulk_parcels',
          triggerApplyBulk: '#yith_shippo_bulk_parcels_triggered',
          bulkActionSelect: '#yith-shippo-parcel-bulk-action',
          form: '#plugin-fw-wc',
          addParcelSubmit: '#yith-shippo-add-parcel-modal .submit',
          floatButton: '#yith-plugin-fw-float-save-button',
          parcelRow: '.yith-shippo-parcel',
          editParcel: '.yith-shippo-parcel .action__edit',
          activeParcel: '.active input',
          deleteParcel: '.action__trash'
        },
        fields: {},
        init: function() {
          this.initFields();

          $(this.selector.floatButton).hide();
          $(document).on('click', this.selector.addParcelButton, this.addParcel);
          $(document).on('click', this.selector.editParcel, this.editParcel);
          $(document).on('click', this.selector.checkAll, this.checkAllbulkItems);
          $(document).on('click', this.selector.applyBulk, this.triggerBulkAction);
          $(document).
              on('click', this.selector.addParcelSubmit, this.validateParcelBoxes);
          $(document).
              on('click', this.selector.activeParcel, this.activeParcel);
          $(document).on('click', this.selector.deleteParcel, this.deleteParcel);

        },
        initFields: function() {
          this.fields = {
            parcelType: $(document).find('.parcel-type select'),
            bulkItems: $(document).find('.yith-shippo-bulk-single-action'),
            checkAll: $(document).find('.yith-shippo-bulk-action-check'),
          };

          this.fields.parcelType.on('change', this.loadParcelInformation);
          $(document.body).trigger('wc-enhanced-select-init');
        },
        addParcel: function(e) {
          e.preventDefault();
          var newParcel = $(parcels.template({
            id: 'shippo-' + Date.now(),
            edit: false,
          })).addClass('yith-shippo-new-parcel');
          parcels.modal = yith.ui.modal({
            content: newParcel,
            title: yith_shippo_parcel_params.add_new_parcel_title,
          });
          parcels.initFields();
        },
        editParcel: function(e) {
          e.preventDefault();
          var $this = $(this),
              row = $this.closest('.yith-shippo-parcel'),
              data = row.data('item'),
              shipping_zones = data.shipping_zones;

          data.id = data.ID;
          data.edit = true;

          var editParcel = $(parcels.template(data)).addClass('yith-shippo-new-parcel');
          /* Adjust shipping zone */
          var rows = editParcel.find('.yith-plugin-fw-checkbox-array__row');
          var selectAll = editParcel.find('.yith-shipping-zone-select-all input');
          selectAll.attr('checked', false);
          rows.each(function() {
            var field = $(this).find('input');
            field.attr('checked', false);
            $(this).removeClass('selected');
            var currentVal = field.val();
            for (var zone_id of shipping_zones) {
              if (zone_id === currentVal) {
                field.attr('checked', true);
                $(this).addClass('selected');
              }
            }
          });
          if (rows.length === shipping_zones.length) {
            selectAll.attr('checked', true);
          }
          /* Open modal */
          parcels.modal = yith.ui.modal({
            content: editParcel,
            title: yith_shippo_parcel_params.edit_parcel_title,
          });
          parcels.initFields();
        },
        resetDynamicFields: function(parcelID) {
          for (var dimension of parcels.dynamicFields) {
            $('#yith_shippo_parcel-' + parcelID + '-' + dimension).
                val('').
                attr('disabled', false);
          }
        },
        loadParcelInformation: function() {
          var token = $(this).val(),
              parcelID = $(this).closest('form').find('#parcel-id').val();
          if ('parcel' === token) {
            parcels.resetDynamicFields(parcelID);
            return;
          }
          var res = parcels.shippoParcels.filter(function(o) {
            return o.token == token;
          }).pop();

          if (res) {
            for (var i = 0; i < parcels.dynamicFields.length; i++) {
              if (parcels.dynamicFields[i] !== 'name') {
                $('#yith_shippo_parcel-' + parcelID + '-' +
                    parcels.dynamicFields[i]).
                    val(res[parcels.dynamicFields[i]]).
                    attr('disabled', true);
              } else {
                $('#yith_shippo_parcel-' + parcelID + '-' +
                    parcels.dynamicFields[i]).
                    val(res.carrier + ' ' + res.name);
              }
            }

          }

        },

        checkAllbulkItems: function() {
          parcels.fields.bulkItems.attr('checked', $(this).is(':checked'));
        },

        triggerBulkAction: function(e) {
          e.preventDefault();
          e.stopPropagation();
          window.onbeforeunload = null;
          if ('delete_parcel_boxes' ===
              $(parcels.selector.bulkActionSelect).val()) {
            yith.ui.confirm({
              title: yith_shippo_parcel_params.delete_confirmation_title,
              message: yith_shippo_parcel_params.delete_confirmation_message,
              confirmButtonType: 'delete',
              closeAfterConfirm: false,
              onConfirm: function() {
                $(parcels.selector.triggerApplyBulk).val(1);
                $(parcels.selector.form).submit();
              },
            });
          } else {
            $(parcels.selector.triggerApplyBulk).val(1);
            $(parcels.selector.form).submit();
          }
        },
        activeParcel: function(e) {
          e.preventDefault();
          var id = $(this).closest('tr').data('id');
          $.ajax({
            type: 'POST',
            url: yith_shippo_parcel_params.ajaxurl,
            data: {
              'id': id,
              'enabled': $(this).is(':checked'),
              'security': yith_shippo_parcel_params.security.enableParcelNonce,
              'action': yith_shippo_parcel_params.actions.enableParcel,
            },
            success: function(response) {
              if (typeof response.error !== 'undefined') {
                console.log(response.error);
              }
            },
          });
        },
        deleteParcel: function(e) {
          e.preventDefault();
          var id = $(this).closest('tr').data('id');

          yith.ui.confirm({
            title: yith_shippo_parcel_params.delete_confirmation_title,
            message: yith_shippo_parcel_params.delete_confirmation_message,
            confirmButtonType: 'delete',
            closeAfterConfirm: true,
            onConfirm: function() {
              parcels.parceList.block(parcels.blockArgs);
              $.ajax({
                type: 'POST',
                url: yith_shippo_parcel_params.ajaxurl,
                data: {
                  'id': id,
                  'security': yith_shippo_parcel_params.security.deleteParcelNonce,
                  'action': yith_shippo_parcel_params.actions.deleteParcel,
                },
                success: function(response) {
                  if (response.success && typeof response.data !== 'undefined') {
                    parcels.mainContent.html(response.data);
                    parcels.parceList.unblock();
                    parcels.initFields();
                  }
                  if (typeof response.error !== 'undefined') {
                    console.log(response.error);
                  }
                },
              });
            },
          });

        },
        validateParcelBoxes: function(e) {
          e.preventDefault();
          e.stopPropagation();
          window.onbeforeunload = null;
          var form = $(document).find('#yith-shippo-add-parcel');
          var fieldsToCheck = ['width', 'length', 'height'];
          var parcel_id = $(document).find('#parcel-id').val();
          var firstErrorFields = false;
          for (var i = 0; i < fieldsToCheck.length; i++) {
            var currentFieldInput = $(document).
                find('#yith_shippo_parcel-' + parcel_id + '-' + fieldsToCheck[i]);
            if (!currentFieldInput.isNumeric()) {
              currentFieldInput.addClass('required');
              firstErrorFields = currentFieldInput;
            } else {
              currentFieldInput.removeClass('required');
            }
          }

          fieldsToCheck = ['weight', 'inner_padding', 'max_weight'];
          for (var i = 0; i < fieldsToCheck.length; i++) {
            var currentFieldInput = $(document).
                find('#yith_shippo_parcel-' + parcel_id + '-' + fieldsToCheck[i]);
            var currentValue = currentFieldInput.val();
            if (currentValue) {
              var newValue = currentValue.replace(',', '.');
              currentFieldInput.val(newValue);
            }
          }
          if (!firstErrorFields) {
            var formData = new FormData();
            var params = $(form).serializeArray();

            $.each(params, function(i, val) {
              formData.append(val.name, val.value);
            });

            formData.append('action', yith_shippo_parcel_params.actions.saveParcel);
            formData.append('security', yith_shippo_parcel_params.security.saveParcelNonce);
            parcels.parceList.block(parcels.blockArgs);
            $.ajax({
              type: 'POST',
              url: yith_shippo_parcel_params.ajaxurl,
              data: formData,
              dataType: 'json',
              contentType: false,
              processData: false,
              success: function(response) {
                if (response.success && typeof response.data !== 'undefined') {
                  parcels.mainContent.html(response.data);
                  parcels.parceList.unblock();
                  parcels.modal.close();
                  parcels.initFields();
                }
                if (typeof response.error !== 'undefined') {
                  console.log(response.error);
                }
              }
            });
          }
        },
      };

      parcels.parceList.length > 0 && parcels.init();

      var parcelTab = {
        selector: {
          additionalWeight: '#yith_shippo_additional_weight',
          mainButton: '#main-save-button, #yith-plugin-fw-float-save-button',
          floatButton: '#yith-plugin-fw-float-save-button',
          form: '#plugin-fw-wc',
        },
        fields: {
          additionalWeightType: $('#yith_shippo_additional_weight_type'),
        },
        init: function() {
          this.fields.additionalWeightType.on('change',
              this.switchAdditionalWeightType).trigger('change');
          if ($('#yith_shippo_min_parcel_dimension').length > 0) {
            $(document).
                on('click', this.selector.mainButton,
                    this.validateDefaultParcelBoxDimensions);
            $(document).
                on('click', this.selector.floatButton,
                    this.validateDefaultParcelBoxDimensions);
          }

        },
        switchAdditionalWeightType: function() {
          var $t = parcelTab.fields.additionalWeightType,
              fixed = $(parcelTab.selector.additionalWeight).find('.fixed'),
              percentage = $(parcelTab.selector.additionalWeight).
                  find('.percentage');
          if ('fixed' === $t.val()) {
            percentage.hide();
            fixed.show();
          } else {
            percentage.show();
            fixed.hide();
          }
        },
        validateDefaultParcelBoxDimensions: function(e) {
          e.preventDefault();
          e.stopPropagation();
          window.onbeforeunload = null;
          var fieldsToCheck = [
            'min-width',
            'min-length',
            'min-height',
            'min-weight'];
          var firstErrorFields = false;

          for (var i = 0; i < fieldsToCheck.length; i++) {
            var currentFieldInput = $(document).
                find('#yith_shippo_min_parcel_dimension_' + fieldsToCheck[i]);

            if (!currentFieldInput.isNumeric()) {
              currentFieldInput.addClass('required');
              if (!firstErrorFields) {
                firstErrorFields = true;
                currentFieldInput.scroll_to();
              }
            } else {
              currentFieldInput.removeClass('required');
            }
          }

          if (!firstErrorFields) {
            $(parcelTab.selector.form).submit();
          }
        },
      };

      parcelTab.init();

    },
)
;
