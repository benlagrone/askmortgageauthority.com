/* global yith_shippo_admin_params */
jQuery(function($) {

  var is_onboarding = $('#yith-bh-onboarding').length > 0;

  $( '#woocommerce-product-data' ).on('woocommerce_variations_loaded', function(){
    $(document).find( 'p.dimensions_field' ).closest('div').find('input').removeAttr('placeholder');
  })

  $.fn.isNumeric = function() {
    var str = $(this).val();
    if (typeof str !== 'string') return false;
    return !isNaN(str) && !isNaN(parseFloat(str));
  };
  $.fn.scroll_to = function() {
    $('html,body').animate({
      scrollTop: $(this).offset().top - 100,
    }, 'slow');
  };

  /* Init the shipping zone field */
  var shipping_zone_fields = {
    selectors: {
      wrapper: '.yith-shippo-shipping-zones',
      option_row: '.yith-plugin-fw-checkbox-array__row',
      options: '.yith-plugin-fw-checkbox-array__row span',
      selectAll: '.yith-shipping-zone-select-all',
    },
    triggerAllEvent: true,
    init: function() {
      $(document).
          on('mouseout', shipping_zone_fields.selectors.wrapper + ' ' +
              shipping_zone_fields.selectors.options, this.toggleClasses);
      $(document).
          on('click', shipping_zone_fields.selectors.wrapper + ' ' +
              shipping_zone_fields.selectors.selectAll + ' input',
              this.changeSelectAll);
      $(document).
          on('click', shipping_zone_fields.selectors.wrapper + ' ' +
              shipping_zone_fields.selectors.options, this.changeSpanOption);
      $(document).
          on('click', shipping_zone_fields.selectors.wrapper + ' ' +
              shipping_zone_fields.selectors.option_row + ' input',
              this.changeOption);
      this.initCheckedOptions();
    },
    initCheckedOptions: function() {
      var rows = $(document).
          find(shipping_zone_fields.selectors.wrapper + ' ' +
              shipping_zone_fields.selectors.options);

      rows.each(function() {
        var row = $(this).closest(shipping_zone_fields.selectors.option_row),
            cb = row.find('input'),
            is_checked = cb.is(':checked');

        if (!is_checked) {
          row.removeClass('selected');
        } else {
          row.addClass('selected');
        }
      });
    },
    rowHasUncheckedField: function(the_field) {
      var rows = the_field.find(shipping_zone_fields.selectors.options),
          res = false;

      rows.each(function() {
        var row = $(this).closest(shipping_zone_fields.selectors.option_row),
            cb = row.find('input'),
            is_checked = cb.is(':checked');
        if (!is_checked) {
          res = true;
        }
      });
      return res;
    },
    toggleSelectAll: function(row) {
      var field = row.closest(shipping_zone_fields.selectors.wrapper),
          selectAll = field.find(
              shipping_zone_fields.selectors.selectAll + ' input'),
          uncheck = false;

      if (shipping_zone_fields.rowHasUncheckedField(field)) {
        uncheck = true;
      }
      shipping_zone_fields.triggerAllEvent = false;
      if (uncheck) {
        if (selectAll.is(':checked')) {
          selectAll.trigger('click');
        }
      } else {
        if (!selectAll.is(':checked')) {
          selectAll.trigger('click');
        }
      }
      shipping_zone_fields.triggerAllEvent = true;
    },
    changeSelectAll: function(e) {
      e.stopImmediatePropagation();
      if (shipping_zone_fields.triggerAllEvent) {
        var all_is_checked = $(this).is(':checked');

        var rows = $(this).
            parents(shipping_zone_fields.selectors.wrapper).
            find(shipping_zone_fields.selectors.option_row);

        rows.each(function() {
          var cb = $(this).find('input');
          if (all_is_checked) {
            cb.attr('checked', true);
            $(this).addClass('selected');
          } else {
            cb.attr('checked', false);
            $(this).removeClass('selected');
          }
        });
      }
    },
    changeSpanOption: function(e) {
      var input = $(this).prev('input');
      input.trigger('click');
    },
    changeOption: function(e) {
      var row = $(this).parent();
      if (!$(this).is(':checked')) {
        row.removeClass('selected');
        row.addClass('deselected');
      } else {
        row.addClass('selected');
        row.removeClass('deselected');
      }
      shipping_zone_fields.toggleSelectAll(row);
    },
    toggleClasses: function() {
      var row = $(this).closest(shipping_zone_fields.selectors.option_row);

      if (row.hasClass('deselected')) {
        row.removeClass('deselected');
      }
    },

  };

  $(document.body).on('yith-shipping-zone-field-init', function() {
    shipping_zone_fields.init();
  }).trigger('yith-shipping-zone-field-init');

  /**
   * Update the button for the onboarding
   *
   * @param connect
   */
  var updateButton = function(ev) {

    if ($('.yith-bh-onboarding-connect-cta').length > 0) {
      $('.yith-bh-onboarding-connect-cta').parents('.yith-bh-onboarding-tabs').find('li[data-tab="main"]').click();
      $('.yith-bh-onboarding-connect-cta').hide();
    }
    var data = ev.detail.connected ? {} : {yith_shippo_disconnect: 'yes'};
    $.post(document.location.href, data).done(function(data) {
      if (data !== '') {
        var c = $('<div></div>').html(data),
            button = c.find('#yith-shippo-onboarding-button');
        $('#yith-shippo-onboarding-button').html(button.html());
      }
    });
  };

  var openModal = function (){
    var h = 600;
    var w = 500;
    var y = window.outerHeight / 2 + window.screenY - (h / 2);
    var x = window.outerWidth / 2 + window.screenX - (w / 2);
    window.open(yith_shippo_admin_params.shippo_url, 'Go Shippo',
        'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' +
        w + ', height=' + h + ', top=' + y + ', left=' + x);
  }

  $(document).on('click', '.yith-shippo-connect-account', function(e) {
    e.preventDefault();
   openModal();
  });
  $(document).on('onboarding-shippo-popup',function (e){

    openModal();
  });
  $(document).on('yith-shippo-update-connection-button', updateButton);

  $(document).on('click', '.yith-shippo-disconnect-account', function(e) {
    e.preventDefault();
    yith.ui.confirm(
        {
          title: yith_shippo_admin_params.disconnect_popup.title,
          message: yith_shippo_admin_params.disconnect_popup.message,
          confirmButtonType: 'delete',
          confirmButton: yith_shippo_admin_params.disconnect_popup.button,
          onConfirm: function() {
            updateButton({detail: {connected: false}});
          },
        },
    );
  });


});
