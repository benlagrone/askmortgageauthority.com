/**
 * Admin JS
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH PayPal Payments for WooCommerce
 * @version 1.0.0
 */

(function ($) {
  var onboarding = $(".yith-bh-onboarding-tabs").length > 0;

  // on change environment, submit the form and reload the page
  $(document).on(
    "change",
    "#yith_ppwc_gateway_options\\[environment\\] input",
    function (e) {
      if (
        !$(this)
          .closest(".yith-plugin-fw-radio")
          .hasClass("yith-plugin-fw-radio--initialized")
      ) {
        return false;
      }

      if (onboarding) {
        var data = $(this).closest("form").serialize();
        $.post(document.location.href, data).done(function (data) {
          window.location.reload();
        });
        return false;
      }

      $(this).closest("form").submit();
    }
  );

  if ($("#yith_ppwc_button_shape input").length > 0) {
    $(document).on("change", "#yith_ppwc_button_shape input", function () {
      var $t = $(this),
        currentValue = $t.val();
      $(document).find(".yith-plugin-fw-select-images__item").hide();
      if ($t.is(":checked")) {
        if ("pill" === currentValue) {
          $('[data-key*="-pill"]').show();
        } else {
          $('[data-key*="-rect"]').show();
        }
      } else {
        if ("rect" === currentValue) {
          $('[data-key*="-pill"]').show();
        } else {
          $('[data-key*="-rect"]').show();
        }
      }
    });
    $("#yith_ppwc_button_shape input").change();
  }

  if ($("#yith_ppwc_button_on-checkout").length > 0) {
    $(document).on("change", "#yith_ppwc_button_on input", function () {
      if (
        $("#yith_ppwc_button_on-cart").is(":checked") ||
        $("#yith_ppwc_button_on-product").is(":checked")
      ) {
        $("#yith_ppwc_gateway_options\\[fast_checkout\\]").closest("tr").show();
      } else {
        $("#yith_ppwc_gateway_options\\[fast_checkout\\]").closest("tr").hide();
      }
    });

    $("#yith_ppwc_button_on input").change();
  }

  function ajaxRequest(data, item) {
    data["action"] = yith_ppwc_admin.ajaxAction;
    data["security"] = yith_ppwc_admin.ajaxNonce;

    return $.ajax({
      type: "POST",
      url: ajaxurl,
      data: data,
      beforeSend: function () {
        item.block({
          message: null,
          overlayCSS: {
            background:
              "url(" + yith_ppwc_admin.ajaxLoader + ") #fff no-repeat center",
            opacity: 0.6,
          },
        });
      },
      complete: function (response) {
        //item.unblock();
      },
    });
  }

  // Disconnect account
  $(document).on("click", ".onboarding-action-buttons a.logout", function (ev) {
    ev.preventDefault();

    var url = $(this).attr("href");

    if ("undefined" === typeof yith.ui) {
      return;
    }

    yith.ui.confirm({
      title: yith_ppwc_admin.confirm_logout_title,
      message: yith_ppwc_admin.confirm_logout_content,
      confirmButtonType: "delete",
      confirmButton: yith_ppwc_admin.continue,
      closeAfterConfirm: false,
      onConfirm: function () {
        window.location.href = url;
      },
    });
  });

  $("#yith_ppwc_button_funding_sources-card")
    .on("change", function () {
      var $t = $(this);
      var $option = $(document)
        .find("#yith_ppwc_button_credit_cards")
        .closest("tr");

      if ($t.is(":checked")) {
        $option.show();
      } else {
        $option.hide();
      }
    })
    .change();

  if( yith_ppwc_admin.cc_enabled > 0 ){
    var ccCheck = $('#yith_ppwc_button_funding_sources-card'),
        notAvailable = $('<small></small>').addClass('cc-not-available').html( yith_ppwc_admin.cc_not_available );
    ccCheck.attr('disabled', true );
    ccCheck.closest('div').append(notAvailable);
  }

  $('.yith-plugins_page_yith_paypal_payments .yith-plugin-fw__panel__content__page').find('[data-deps]').each(function () {
    var t = $(this),
        wrap = t.closest('.yith-plugin-fw__panel__option'),
        deps = t.attr('data-deps').split(','),
        values = t.attr('data-deps_value').split(','),
        conditions = [];

    $.each(deps, function (i, dep) {
      $('[name="' + dep + '"]').on('change', function () {

        var value = this.value,
            check_values = '';

        // exclude radio if not checked
        if (this.type == 'radio' && !$(this).is(':checked')) {
          return;
        }

        if (this.type == 'checkbox') {
          value = $(this).is(':checked') ? 'yes' : 'no';
        }

        check_values = values[i] + ''; // force to string
        check_values = check_values.split('|');
        conditions[i] = $.inArray(value, check_values) !== -1;

        if ($.inArray(false, conditions) === -1) {
          wrap.fadeIn();
        } else {
          wrap.fadeOut();
        }

      }).change();
    });
  });

  /* Google pay enable button */
  $('.yith-plugins_page_yith_paypal_payments .yith-plugin-fw-onoff-container.disabled > input').attr('disabled', true);
})(jQuery);
