/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/typeof.js
function _typeof(obj) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
  }, _typeof(obj);
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js

function _toPrimitive(input, hint) {
  if (_typeof(input) !== "object" || input === null) return input;
  var prim = input[Symbol.toPrimitive];
  if (prim !== undefined) {
    var res = prim.call(input, hint || "default");
    if (_typeof(res) !== "object") return res;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (hint === "string" ? String : Number)(input);
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js


function _toPropertyKey(arg) {
  var key = _toPrimitive(arg, "string");
  return _typeof(key) === "symbol" ? key : String(key);
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/createClass.js

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor);
  }
}
function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  Object.defineProperty(Constructor, "prototype", {
    writable: false
  });
  return Constructor;
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/defineProperty.js

function defineProperty_defineProperty(obj, key, value) {
  key = _toPropertyKey(key);
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }
  return obj;
}
;// CONCATENATED MODULE: ./assets/js/src/globals.js


/* global jQuery yith yithStripePayments */

// these constants will be wrapped inside webpack closure, to prevent collisions

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
var $ = jQuery,
  $document = $(document),
  $body = $('body'),
  block = function block($el) {
    if ('undefined' === typeof $.fn.block) {
      return false;
    }
    try {
      $el.block({
        message: null,
        overlayCSS: {
          background: '#fff',
          opacity: 0.6
        }
      });
      return $el;
    } catch (e) {
      return false;
    }
  },
  unblock = function unblock($el) {
    if ('undefined' === typeof $.fn.unblock) {
      return false;
    }
    try {
      $el.unblock();
    } catch (e) {
      return false;
    }
  },
  globals_confirm = function confirm(title, message, args) {
    return new Promise(function (resolve, reject) {
      var _yith, _yith$ui;
      // if can't display modal, accept by default
      if ('undefined' === typeof ((_yith = yith) === null || _yith === void 0 ? void 0 : (_yith$ui = _yith.ui) === null || _yith$ui === void 0 ? void 0 : _yith$ui.confirm)) {
        reject(new Error('Missing yith.ui utilities'));
      }
      var options = _objectSpread({
        title: title || labels.generic_confirm_title,
        message: message || labels.generic_confirm_message
      }, args);
      options.onConfirm = function () {
        return resolve(true);
      };
      options.onCancel = reject;
      yith.ui.confirm(options);
    });
  },
  _ref = typeof yithStripePayments !== 'undefined' ? yithStripePayments : {},
  ajaxUrl = _ref.ajax_url,
  labels = _ref.labels,
  nonces = _ref.nonces;

;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js
function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js
function _iterableToArrayLimit(arr, i) {
  var _i = null == arr ? null : "undefined" != typeof Symbol && arr[Symbol.iterator] || arr["@@iterator"];
  if (null != _i) {
    var _s,
      _e,
      _x,
      _r,
      _arr = [],
      _n = !0,
      _d = !1;
    try {
      if (_x = (_i = _i.call(arr)).next, 0 === i) {
        if (Object(_i) !== _i) return;
        _n = !1;
      } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0);
    } catch (err) {
      _d = !0, _e = err;
    } finally {
      try {
        if (!_n && null != _i["return"] && (_r = _i["return"](), Object(_r) !== _r)) return;
      } finally {
        if (_d) throw _e;
      }
    }
    return _arr;
  }
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js




function _slicedToArray(arr, i) {
  return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest();
}
;// CONCATENATED MODULE: ./assets/js/src/modules/ajax.js



function ajax_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function ajax_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ajax_ownKeys(Object(source), !0).forEach(function (key) { defineProperty_defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ajax_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

var request = function request(method, action, security, params, args) {
    var _request$activeReques;
    if (!(request !== null && request !== void 0 && request.activeRequests)) {
      request.activeRequests = {};
    }

    // retrieve wrapper as current context.
    var $wrapper = $(this);
    if (params instanceof FormData) {
      params.append('action', "yith_stripe_payments_".concat(action));
      params.append('security', nonces !== null && nonces !== void 0 && nonces[security] ? nonces === null || nonces === void 0 ? void 0 : nonces[security] : security);
    } else {
      params = ajax_objectSpread({
        action: "yith_stripe_payments_".concat(action),
        security: nonces !== null && nonces !== void 0 && nonces[security] ? nonces === null || nonces === void 0 ? void 0 : nonces[security] : security
      }, params);
    }
    var requestKey = "".concat(method, "-").concat(action);
    var ajaxArgs = ajax_objectSpread({
      url: ajaxUrl,
      data: params,
      method: method,
      beforeSend: function beforeSend() {
        return $wrapper.length && block($wrapper);
      },
      complete: function complete() {
        return $wrapper.length && unblock($wrapper);
      }
    }, args);

    // eslint-disable-next-line
    if (request !== null && request !== void 0 && request.activeRequests[requestKey] && typeof ((_request$activeReques = request.activeRequests[requestKey]) === null || _request$activeReques === void 0 ? void 0 : _request$activeReques.abort) === 'function') {
      request.activeRequests[requestKey].abort();
    }
    var xhr = $.ajax(ajaxArgs);
    request.activeRequests[requestKey] = xhr;
    xhr.always(function () {
      return delete request.activeRequests[requestKey];
    });
    return xhr;
  },
  get = function get() {
    for (var _len = arguments.length, params = new Array(_len), _key = 0; _key < _len; _key++) {
      params[_key] = arguments[_key];
    }
    return request.call.apply(request, [this, 'get'].concat(params));
  },
  post = function post() {
    for (var _len2 = arguments.length, params = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      params[_key2] = arguments[_key2];
    }
    return request.call.apply(request, [this, 'post'].concat(params));
  };
/* harmony default export */ const ajax = ({
  request: request,
  get: get,
  post: post
});
;// CONCATENATED MODULE: ./assets/js/admin/src/modules/OnboardingButton.js


/* globals jQuery yith yithStripePayments */




function OnboardingButton_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function OnboardingButton_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? OnboardingButton_ownKeys(Object(source), !0).forEach(function (key) { defineProperty_defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : OnboardingButton_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }



/**
 * Class that handles Onboarding button interactions
 */
var OnboardingButton = /*#__PURE__*/function () {
  /**
   * Constructor method
   *
   * @param {jQuery} $el     Object jQuery reference to onboarding button node
   * @param {Object} options Options for object construction
   */
  function OnboardingButton($el) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    _classCallCheck(this, OnboardingButton);
    /**
     * Set of options
     */
    defineProperty_defineProperty(this, "options", {
      reloadOnClose: true,
      addBackdrop: true
    });
    /**
     * jQuery button element
     */
    defineProperty_defineProperty(this, "$button", void 0);
    /**
     * Instance of the popup window opened to contain onboard flow
     */
    defineProperty_defineProperty(this, "onboardingPopup", void 0);
    /**
     * Holds reference to the Interval checking for onboarding popup closure
     */
    defineProperty_defineProperty(this, "popupClosePool", void 0);
    this.$button = $el;
    this.options = OnboardingButton_objectSpread(OnboardingButton_objectSpread({}, this.options), options);
    this.init();
  }

  /**
   * Init events specific of this object
   */
  _createClass(OnboardingButton, [{
    key: "init",
    value: function init() {
      this.initClick();
    }

    /**
     * Init click on the Onboarding button
     */
  }, {
    key: "initClick",
    value: function initClick() {
      var _this = this;
      this.$button.on('click', function (ev) {
        ev.preventDefault();
        _this.onClick();
      });
    }

    /**
     * Handles click on the onboarding button
     */
  }, {
    key: "onClick",
    value: function onClick() {
      var _this2 = this;
      ajax.post.call(this.$button, 'process_onboarding', 'process_onboarding', {
        env: this.getEnv()
      }).then(function (response) {
        var _response$data;
        // something went wrong with connection process; reload to show possible notices.
        if (!(response !== null && response !== void 0 && (_response$data = response.data) !== null && _response$data !== void 0 && _response$data.onboard_link)) {
          return window.location.reload();
        }
        _this2.openOnboardingWindow(response.data.onboard_link);
      });
    }

    /**
     * Returns env for API call (either global defined one, or one coming from dedicated option in current page)
     *
     * @return {string} Current environment
     */
  }, {
    key: "getEnv",
    value: function getEnv() {
      var $envRadio = $(':input[id*="environment"]'),
        $checked = $envRadio.filter(':checked'),
        val = $checked.val();
      var env = yithStripePayments.env;
      if (val && ['live', 'test'].includes(val)) {
        env = val;
      }
      return env;
    }

    /**
     * Adds backdrop to current window
     */
  }, {
    key: "addBackdrop",
    value: function addBackdrop() {
      this.options.addBackdrop && $(document.body).addClass('stripe-onboarding-open');
    }

    /**
     * Removes backdrop from current window
     */
  }, {
    key: "removeBackdrop",
    value: function removeBackdrop() {
      this.options.addBackdrop && $(document.body).removeClass('stripe-onboarding-open');
    }

    /**
     * Opens onboarding popup, given the URL to visit
     *
     * @param {string} onboardLink Url to visit for onboarding
     */
  }, {
    key: "openOnboardingWindow",
    value: function openOnboardingWindow(onboardLink) {
      var screenHeight = screen.height,
        screenWidth = screen.width,
        features = Object.entries({
          popup: true,
          left: screenWidth / 6,
          top: screenHeight / 6,
          width: 2 * screenWidth / 3,
          height: 2 * screenHeight / 3
        }).map(function (_ref) {
          var _ref2 = _slicedToArray(_ref, 2),
            i = _ref2[0],
            v = _ref2[1];
          return "".concat(i, "=").concat(v);
        }).join(',');

      // open popup window.
      this.onboardingPopup = window.open(onboardLink, 'Stripe', features);

      // starts timeout to check for window closure.
      this.startPopupClosePool();

      // add backdrop on body
      this.addBackdrop();
    }

    /**
     * Starts the pool that checks for onboarding popup closure
     */
  }, {
    key: "startPopupClosePool",
    value: function startPopupClosePool() {
      var _this3 = this;
      if (this.popupClosePool) {
        clearInterval(this.popupClosePool);
      }
      this.popupClosePool = setInterval(function () {
        if (!_this3.onboardingPopup.closed) {
          return;
        }
        _this3.onboardingPopup = null;
        _this3.removeBackdrop();
        clearInterval(_this3.popupClosePool);
        _this3.options.reloadOnClose && window.location.reload();
      }, 1000);
    }

    /**
     * Removes event listeners and cleans up resources.
     */
  }, {
    key: "destroy",
    value: function destroy() {
      this.$button.off();
    }
  }]);
  return OnboardingButton;
}();

;// CONCATENATED MODULE: ./assets/js/admin/src/modules/OnboardingWidget.js


/* globals jQuery yith yithStripePayments */







/**
 * Class that handles Onboarding button interactions
 */
var OnboardingWidget = /*#__PURE__*/function () {
  /**
   * Constructor method
   *
   * @param {jQuery} $el object jQuery reference to onboarding button node
   */
  function OnboardingWidget($el) {
    _classCallCheck(this, OnboardingWidget);
    /**
     * jQuery wrapper element.
     */
    defineProperty_defineProperty(this, "$wrapper", void 0);
    /**
     * jQuery connect button element
     */
    defineProperty_defineProperty(this, "$connectButton", void 0);
    /**
     * jQuery refresh button element
     */
    defineProperty_defineProperty(this, "$refreshButton", void 0);
    /**
     * jQuery revoke button element
     */
    defineProperty_defineProperty(this, "$revokeButton", void 0);
    this.$wrapper = $el;
    this.$connectButton = this.$wrapper.find('.yith-stripe-payments__onboarding__button').add('.yith-stripe-payments__onboarding__continue');
    this.$refreshButton = this.$wrapper.find('.yith-stripe-payments__onboarding__refresh');
    this.$revokeButton = this.$wrapper.find('.yith-stripe-payments__onboarding__revoke');
    this.init();
  }

  /**
   * Init events specific of this object
   */
  _createClass(OnboardingWidget, [{
    key: "init",
    value: function init() {
      var _this = this;
      this.$connectButton.length && this.$connectButton.each(function (i, el) {
        return new OnboardingButton($(el));
      });
      this.$refreshButton.length && this.$refreshButton.on('click', function (ev) {
        ev.preventDefault();
        _this.refresh();
      });
      this.$revokeButton.length && this.$revokeButton.on('click', function (ev) {
        ev.preventDefault();
        _this.revoke();
      });
    }
  }, {
    key: "refresh",
    value: function refresh() {
      ajax.post.call(this.$refreshButton, 'refresh_connection_status', 'refresh_connection_status').then(function () {
        return window.location.reload();
      });
    }
  }, {
    key: "revoke",
    value: function revoke() {
      var modalClass = 'yith-stripe-payments__onboarding__revoke-modal';
      yith.ui.confirm({
        title: labels.confirm_revoke_title,
        message: labels.confirm_revoke_message,
        confirmButton: labels.confirm_revoke_button,
        confirmButtonType: 'delete',
        closeAfterConfirm: false,
        classes: {
          wrap: modalClass
        },
        onConfirm: function onConfirm() {
          ajax.post.call($(".".concat(modalClass, " > .yith-plugin-fw__modal__main")), 'revoke_connection', 'revoke_connection').then(function () {
            return window.location.reload();
          });
        }
      });
    }
  }]);
  return OnboardingWidget;
}();

;// CONCATENATED MODULE: ./assets/js/admin/src/onboarding.js


/* global jQuery yith yithStripePayments */



// init onboarding process.
jQuery(function ($) {
  var $onboardingWidget = $('.yith-stripe-payments__onboarding'),
    $envRadio = $(':input[id*="environment"]');

  // start onboarding widget JS behaviour.
  $onboardingWidget.length && new OnboardingWidget($onboardingWidget);
  window.yithStripePayments.connectOnboarding = function (button) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var $button = $(button);
    if (!$button.length) {
      return;
    }
    return new OnboardingButton($button, options);
  };

  // reloads page when environment option is changed. Double $( fn ) is required to run this code after WC's one, that does the same.
  // @see wp-content/plugins/woocommerce/assets/js/admin/settings.js:93
  $(function () {
    $envRadio.on('change', function (ev) {
      window.onbeforeunload = null;
      $(ev.target).closest('form').submit();
      return false;
    });
  });
});
var __webpack_export_target__ = window;
for(var i in __webpack_exports__) __webpack_export_target__[i] = __webpack_exports__[i];
if(__webpack_exports__.__esModule) Object.defineProperty(__webpack_export_target__, "__esModule", { value: true });
/******/ })()
;
//# sourceMappingURL=yith-stripe-payments-onboarding.bundle.js.map