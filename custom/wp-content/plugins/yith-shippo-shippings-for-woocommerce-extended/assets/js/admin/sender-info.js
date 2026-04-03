/* global yith_shippo_sender_info_params, yith */
jQuery(function ($) {
  var sender = {
    onboardingValidation: true,
    template:
      typeof wp.template !== "undefined"
        ? wp.template("yith-shippo-sender")
        : null,
    modal: false,
    selector: {
      addSenderButton: $(document).find("button.yith-shippo-add-sender"),
      mainButton: "#yith-plugin-fw__panel__content__save",
      mainBox: ".single-sender-info-wrapper",
      mainForm: "#plugin-fw-wc",
      editSender: ".action__edit",
      deleteSender: ".action__trash",
      setDefault: ".set-default",
    },

    initFields: function () {
      if( typeof selectWoo !== 'undefined'){
        $("#yith-shippo-sender-info-country_state").selectWoo();
      }
    },

    init: function () {
      this.initFields();
      $(document).on('submit','#plugin-fw-wc', function(){
        return false;
      });

      if (this.selector.addSenderButton.length > 0) {
        $(document).find("h1").append(this.selector.addSenderButton);
      }

      // remove the save and reset button when the shipping zones are activated.
      if ($("table#sender-info").length > 0) {
        $(
          "p.submit, #yith-plugin-fw-float-save-button, #plugin-fw-wc-reset"
        ).hide();
      }
      $(document).on(
        "change",
        "#yith-shippo-sender-use_wc_address",
        this.fillAddress
      );
      $(document).find("#yith-shippo-sender-use_wc_address").change();

      $(document).on(
        "click",
        this.selector.mainButton,
        this.validateSenderInfo
      );
      $(document).on(
        "click",
        ".yith-plugin-fw__modal__main #yith-shippo-save-sender",
        this.validateSenderInfo
      );

      $(document).on("click", "button.yith-shippo-add-sender", this.addSender);
      $(document).on("click", this.selector.editSender, this.editSender);
      $(document).on("click", this.selector.deleteSender, this.deleteSender);
      $(document).on("click", this.selector.setDefault, this.setDefault);

      $(document).on(
        "yith_onboarding_form_submit_validation",
        this.onboardingNeedValidation.bind(this)
      );

      $(document).on(
        "yith_onboarding_validate_form_submit",
        this.onboardingValidate.bind(this)
      );
    },
    onboardingNeedValidation: function () {
      return this.onboardingValidation;
    },
    onboardingValidate: function (event, form) {
      var is_empty = sender.checkEmptyFields();
      if (!is_empty) {
        var is_valid_email = this.checkValidEmail(
          $("#yith-shippo-sender-info-email")
        );
      }
      if (!is_empty && is_valid_email) {
        this.onboardingValidation = false;
        form.submit(); // trigger again the submit form
      }
    },
    block: function (element) {
      var blockArgs = {
        message: "",
        overlayCSS: {
          backgroundColor: "#FFFFFF",
          opacity: 0.8,
          cursor: "wait",
        },
      };
      element.block(blockArgs);
    },
    unblock: function (element) {
      element.unblock();
    },
    fillAddress: function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      if (
        $(document).find("#yith-shippo-sender-use_wc_address").is(":checked")
      ) {
        var wcAddress =
          yith_shippo_sender_info_params.woocommerce_shop_base_info;
        for (var key in wcAddress) {
          $(document)
            .find("#yith-shippo-sender-info-" + key)
            .val(wcAddress[key])
            .attr("disabled", true).change();
        }
      } else {
        $(document)
          .find(
            ".yith-shippo-sender-wrap input",
            ".yith-shippo-sender-wrap select"
          )
          .attr("disabled", false);
        $(document)
          .find(".yith-shippo-sender-wrap select")
          .attr("disabled", false);
      }
      sender.initFields();
    },
    checkEmptyFields: function () {
      var is_empty = false;
      document
        .querySelectorAll(".yith-shippo-validate")
        .forEach(function (item) {
          let empty_field = $(item)
            .closest(".yith-single-text")
            .find(".empty-field");

          if ("" === $(item).val()) {
            is_empty = true;
            if (!empty_field.length) {
              $(
                "<span class='empty-field description'>" +
                  yith_shippo_sender_info_params.emptyField +
                  "</span>"
              ).insertAfter($(item));
            }
            empty_field.show();
            $(item).addClass("is-empty");
          } else {
            empty_field.hide();
            $(item).removeClass("is-empty");
          }
        });
      return is_empty;
    },
    checkValidEmail: function (data) {
      var is_valid = false;
      var validRegex =
        /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;

      if ($(data).val().match(validRegex)) {
        is_valid = true;
      } else {
        $(
          "<span class='empty-field description'>" +
            yith_shippo_sender_info_params.invalidEmailField +
            "</span>"
        ).insertAfter($(data));
      }
      return is_valid;
    },
    validateSenderInfo: function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      var is_empty = sender.checkEmptyFields($(".yith-shippo-validate"));
      if (!is_empty) {
        var is_valid_email = sender.checkValidEmail(
          $("#yith-shippo-sender-info-email")
        );
      }

      if (!is_empty && is_valid_email) {
        var sectionToBlock = $(sender.selector.mainBox);
        var mainForm = $(sender.selector.mainForm);
        if (sender.modal) {
          sectionToBlock = $(document).find(
            "#yith-shippo-sender-info-modal form"
          );
          mainForm = sectionToBlock;
        }
        sender.block(sectionToBlock);

        var formData = new FormData();
        var params = mainForm.serializeArray();

        $.each(params, function (i, val) {
          formData.append(val.name, val.value);
        });

        formData.append(
          "action",
          yith_shippo_sender_info_params.actions.validateSenderInfo
        );
        formData.append(
          "security",
          yith_shippo_sender_info_params.security.addSenderInfoNonce
        );

        var onboarding = $('#yith-bh-onboarding').length > 0;
        $.ajax({
          type: "POST",
          data: formData,
          dataType: "json",
          contentType: false,
          processData: false,
          url: yith_shippo_sender_info_params.ajaxurl,
          success: function (response) {
            if (response.success) {
              if (onboarding) {
                return true;
              }
              if (sender.modal) {
                $(document)
                  .find(".yith-shippo-sender-info")
                  .html(response.data);
                sender.modal.close();
              } else {
                $(document)
                  .find(".yith-shippo-address-validation-error")
                  .html(response.data)
                  .removeClass("error")
                  .addClass("success");
              }
            } else {
              if (response.data) {
                $(document)
                  .find(".yith-shippo-address-validation-error")
                  .html(response.data)
                  .removeClass("success")
                  .addClass("error");
              }
            }
          },
          complete: function () {
            sender.unblock(sectionToBlock);
          },
        });
      } else {
        return false;
      }
    },
    addSender: function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      var newSender = $(
        sender.template({
          id: "shippo-" + Date.now(),
          edit: false,
        })
      );
      sender.modal = yith.ui.modal({
        content: newSender,
        title: yith_shippo_sender_info_params.modal_title,
        width: 510,
      });
      $("#yith-shippo-sender-info-shipping_zones input").attr("checked", false);
      sender.initFields();
    },
    editSender: function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      var $this = $(this),
        row = $this.closest(".yith-shippo-sender-info"),
        data = row.data("item"),
        shipping_zones = data.shipping_zones;
      data.edit = true;
      data.id = row.data("id");
      var editSender = $(sender.template(data));

      /* Adjust shipping zone */
      var rows = editSender.find(".yith-plugin-fw-checkbox-array__row");
      var selectAll = editSender.find(".yith-shipping-zone-select-all input");
      selectAll.attr("checked", false);
      rows.each(function () {
        var field = $(this).find("input");
        field.attr("checked", false);
        $(this).removeClass("selected");
        var currentVal = field.val();
        for (var zone_id of shipping_zones) {
          if (zone_id === currentVal) {
            field.attr("checked", true);
            $(this).addClass("selected");
          }
        }
      });
      if (rows.length === shipping_zones.length) {
        selectAll.attr("checked", true);
      }

      /* Open modal */
      sender.modal = yith.ui.modal({
        content: editSender,
        title: yith_shippo_sender_info_params.modal_title,
        width: 510,
      });

      $(document)
        .find("#yith-shippo-sender-info-country_state")
        .val(data.country_state);
      sender.initFields();
    },
    deleteSender: function (e) {
      e.preventDefault();
      var senderTable = $("#sender-info");
      var id = $(this).closest("tr").data("id");

      yith.ui.confirm({
        title: yith_shippo_sender_info_params.delete_confirmation_title,
        message: yith_shippo_sender_info_params.delete_confirmation_message,
        confirmButtonType: "delete",
        closeAfterConfirm: true,
        onConfirm: function () {
          sender.block(senderTable);
          $.ajax({
            type: "POST",
            url: yith_shippo_sender_info_params.ajaxurl,
            data: {
              id: id,
              security:
                yith_shippo_sender_info_params.security.deleteSenderInfoNonce,
              action: yith_shippo_sender_info_params.actions.deleteSenderInfo,
            },
            success: function (response) {
              if (response.success && typeof response.data !== "undefined") {
                $(document)
                  .find(".yith-shippo-sender-info")
                  .html(response.data);
              }
              if (typeof response.error !== "undefined") {
                console.log(response.error);
              }
            },
          });
        },
      });
    },
    setDefault: function (e) {
      e.preventDefault();
      var senderTable = $("#sender-info");
      var id = $(this).closest("tr").data("id");
      sender.block(senderTable);
      $.ajax({
        type: "POST",
        url: yith_shippo_sender_info_params.ajaxurl,
        data: {
          id: id,
          security:
            yith_shippo_sender_info_params.security.setDefaultSenderInfoNonce,
          action: yith_shippo_sender_info_params.actions.setDefaultSenderInfo,
        },
        success: function (response) {
          if (response.success && typeof response.data !== "undefined") {
            $(document).find(".yith-shippo-sender-info").html(response.data);
          }
          if (typeof response.error !== "undefined") {
            console.log(response.error);
          }
        },
      });
    },
  };
  sender.init();
});
