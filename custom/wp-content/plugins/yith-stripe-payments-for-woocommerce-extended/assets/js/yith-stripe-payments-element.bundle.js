/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ 61:
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var _typeof = (__webpack_require__(698)["default"]);
function _regeneratorRuntime() {
  "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */
  module.exports = _regeneratorRuntime = function _regeneratorRuntime() {
    return exports;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports;
  var exports = {},
    Op = Object.prototype,
    hasOwn = Op.hasOwnProperty,
    defineProperty = Object.defineProperty || function (obj, key, desc) {
      obj[key] = desc.value;
    },
    $Symbol = "function" == typeof Symbol ? Symbol : {},
    iteratorSymbol = $Symbol.iterator || "@@iterator",
    asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator",
    toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";
  function define(obj, key, value) {
    return Object.defineProperty(obj, key, {
      value: value,
      enumerable: !0,
      configurable: !0,
      writable: !0
    }), obj[key];
  }
  try {
    define({}, "");
  } catch (err) {
    define = function define(obj, key, value) {
      return obj[key] = value;
    };
  }
  function wrap(innerFn, outerFn, self, tryLocsList) {
    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator,
      generator = Object.create(protoGenerator.prototype),
      context = new Context(tryLocsList || []);
    return defineProperty(generator, "_invoke", {
      value: makeInvokeMethod(innerFn, self, context)
    }), generator;
  }
  function tryCatch(fn, obj, arg) {
    try {
      return {
        type: "normal",
        arg: fn.call(obj, arg)
      };
    } catch (err) {
      return {
        type: "throw",
        arg: err
      };
    }
  }
  exports.wrap = wrap;
  var ContinueSentinel = {};
  function Generator() {}
  function GeneratorFunction() {}
  function GeneratorFunctionPrototype() {}
  var IteratorPrototype = {};
  define(IteratorPrototype, iteratorSymbol, function () {
    return this;
  });
  var getProto = Object.getPrototypeOf,
    NativeIteratorPrototype = getProto && getProto(getProto(values([])));
  NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype);
  var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype);
  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function (method) {
      define(prototype, method, function (arg) {
        return this._invoke(method, arg);
      });
    });
  }
  function AsyncIterator(generator, PromiseImpl) {
    function invoke(method, arg, resolve, reject) {
      var record = tryCatch(generator[method], generator, arg);
      if ("throw" !== record.type) {
        var result = record.arg,
          value = result.value;
        return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) {
          invoke("next", value, resolve, reject);
        }, function (err) {
          invoke("throw", err, resolve, reject);
        }) : PromiseImpl.resolve(value).then(function (unwrapped) {
          result.value = unwrapped, resolve(result);
        }, function (error) {
          return invoke("throw", error, resolve, reject);
        });
      }
      reject(record.arg);
    }
    var previousPromise;
    defineProperty(this, "_invoke", {
      value: function value(method, arg) {
        function callInvokeWithMethodAndArg() {
          return new PromiseImpl(function (resolve, reject) {
            invoke(method, arg, resolve, reject);
          });
        }
        return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg();
      }
    });
  }
  function makeInvokeMethod(innerFn, self, context) {
    var state = "suspendedStart";
    return function (method, arg) {
      if ("executing" === state) throw new Error("Generator is already running");
      if ("completed" === state) {
        if ("throw" === method) throw arg;
        return doneResult();
      }
      for (context.method = method, context.arg = arg;;) {
        var delegate = context.delegate;
        if (delegate) {
          var delegateResult = maybeInvokeDelegate(delegate, context);
          if (delegateResult) {
            if (delegateResult === ContinueSentinel) continue;
            return delegateResult;
          }
        }
        if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) {
          if ("suspendedStart" === state) throw state = "completed", context.arg;
          context.dispatchException(context.arg);
        } else "return" === context.method && context.abrupt("return", context.arg);
        state = "executing";
        var record = tryCatch(innerFn, self, context);
        if ("normal" === record.type) {
          if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue;
          return {
            value: record.arg,
            done: context.done
          };
        }
        "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg);
      }
    };
  }
  function maybeInvokeDelegate(delegate, context) {
    var methodName = context.method,
      method = delegate.iterator[methodName];
    if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel;
    var record = tryCatch(method, delegate.iterator, context.arg);
    if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel;
    var info = record.arg;
    return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel);
  }
  function pushTryEntry(locs) {
    var entry = {
      tryLoc: locs[0]
    };
    1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry);
  }
  function resetTryEntry(entry) {
    var record = entry.completion || {};
    record.type = "normal", delete record.arg, entry.completion = record;
  }
  function Context(tryLocsList) {
    this.tryEntries = [{
      tryLoc: "root"
    }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0);
  }
  function values(iterable) {
    if (iterable) {
      var iteratorMethod = iterable[iteratorSymbol];
      if (iteratorMethod) return iteratorMethod.call(iterable);
      if ("function" == typeof iterable.next) return iterable;
      if (!isNaN(iterable.length)) {
        var i = -1,
          next = function next() {
            for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next;
            return next.value = undefined, next.done = !0, next;
          };
        return next.next = next;
      }
    }
    return {
      next: doneResult
    };
  }
  function doneResult() {
    return {
      value: undefined,
      done: !0
    };
  }
  return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", {
    value: GeneratorFunctionPrototype,
    configurable: !0
  }), defineProperty(GeneratorFunctionPrototype, "constructor", {
    value: GeneratorFunction,
    configurable: !0
  }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) {
    var ctor = "function" == typeof genFun && genFun.constructor;
    return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name));
  }, exports.mark = function (genFun) {
    return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun;
  }, exports.awrap = function (arg) {
    return {
      __await: arg
    };
  }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () {
    return this;
  }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) {
    void 0 === PromiseImpl && (PromiseImpl = Promise);
    var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl);
    return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) {
      return result.done ? result.value : iter.next();
    });
  }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () {
    return this;
  }), define(Gp, "toString", function () {
    return "[object Generator]";
  }), exports.keys = function (val) {
    var object = Object(val),
      keys = [];
    for (var key in object) keys.push(key);
    return keys.reverse(), function next() {
      for (; keys.length;) {
        var key = keys.pop();
        if (key in object) return next.value = key, next.done = !1, next;
      }
      return next.done = !0, next;
    };
  }, exports.values = values, Context.prototype = {
    constructor: Context,
    reset: function reset(skipTempReset) {
      if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined);
    },
    stop: function stop() {
      this.done = !0;
      var rootRecord = this.tryEntries[0].completion;
      if ("throw" === rootRecord.type) throw rootRecord.arg;
      return this.rval;
    },
    dispatchException: function dispatchException(exception) {
      if (this.done) throw exception;
      var context = this;
      function handle(loc, caught) {
        return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught;
      }
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i],
          record = entry.completion;
        if ("root" === entry.tryLoc) return handle("end");
        if (entry.tryLoc <= this.prev) {
          var hasCatch = hasOwn.call(entry, "catchLoc"),
            hasFinally = hasOwn.call(entry, "finallyLoc");
          if (hasCatch && hasFinally) {
            if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0);
            if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc);
          } else if (hasCatch) {
            if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0);
          } else {
            if (!hasFinally) throw new Error("try statement without catch or finally");
            if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc);
          }
        }
      }
    },
    abrupt: function abrupt(type, arg) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) {
          var finallyEntry = entry;
          break;
        }
      }
      finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null);
      var record = finallyEntry ? finallyEntry.completion : {};
      return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record);
    },
    complete: function complete(record, afterLoc) {
      if ("throw" === record.type) throw record.arg;
      return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel;
    },
    finish: function finish(finallyLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel;
      }
    },
    "catch": function _catch(tryLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc === tryLoc) {
          var record = entry.completion;
          if ("throw" === record.type) {
            var thrown = record.arg;
            resetTryEntry(entry);
          }
          return thrown;
        }
      }
      throw new Error("illegal catch attempt");
    },
    delegateYield: function delegateYield(iterable, resultName, nextLoc) {
      return this.delegate = {
        iterator: values(iterable),
        resultName: resultName,
        nextLoc: nextLoc
      }, "next" === this.method && (this.arg = undefined), ContinueSentinel;
    }
  }, exports;
}
module.exports = _regeneratorRuntime, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ 698:
/***/ ((module) => {

function _typeof(obj) {
  "@babel/helpers - typeof";

  return (module.exports = _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports), _typeof(obj);
}
module.exports = _typeof, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ 687:
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

// TODO(Babel 8): Remove this file.

var runtime = __webpack_require__(61)();
module.exports = runtime;

// Copied from https://github.com/facebook/regenerator/blob/main/packages/runtime/runtime.js#L736=
try {
  regeneratorRuntime = runtime;
} catch (accidentalStrictMode) {
  if (typeof globalThis === "object") {
    globalThis.regeneratorRuntime = runtime;
  } else {
    Function("r", "regeneratorRuntime = r")(runtime);
  }
}


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
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
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
  try {
    var info = gen[key](arg);
    var value = info.value;
  } catch (error) {
    reject(error);
    return;
  }
  if (info.done) {
    resolve(value);
  } else {
    Promise.resolve(value).then(_next, _throw);
  }
}
function _asyncToGenerator(fn) {
  return function () {
    var self = this,
      args = arguments;
    return new Promise(function (resolve, reject) {
      var gen = fn.apply(self, args);
      function _next(value) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
      }
      function _throw(err) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
      }
      _next(undefined);
    });
  };
}
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
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js
function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }
  return self;
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js
function _getPrototypeOf(o) {
  _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/superPropBase.js

function _superPropBase(object, property) {
  while (!Object.prototype.hasOwnProperty.call(object, property)) {
    object = _getPrototypeOf(object);
    if (object === null) break;
  }
  return object;
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/get.js

function _get() {
  if (typeof Reflect !== "undefined" && Reflect.get) {
    _get = Reflect.get.bind();
  } else {
    _get = function _get(target, property, receiver) {
      var base = _superPropBase(target, property);
      if (!base) return;
      var desc = Object.getOwnPropertyDescriptor(base, property);
      if (desc.get) {
        return desc.get.call(arguments.length < 3 ? target : receiver);
      }
      return desc.value;
    };
  }
  return _get.apply(this, arguments);
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js
function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };
  return _setPrototypeOf(o, p);
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/inherits.js

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }
  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  Object.defineProperty(subClass, "prototype", {
    writable: false
  });
  if (superClass) _setPrototypeOf(subClass, superClass);
}
;// CONCATENATED MODULE: ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js


function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  } else if (call !== void 0) {
    throw new TypeError("Derived constructors may only return object or undefined");
  }
  return _assertThisInitialized(self);
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
// EXTERNAL MODULE: ./node_modules/@babel/runtime/regenerator/index.js
var regenerator = __webpack_require__(687);
var regenerator_default = /*#__PURE__*/__webpack_require__.n(regenerator);
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
;// CONCATENATED MODULE: ./assets/js/src/modules/PaymentElement.js


/* globals jQuery Stripe yithStripePayments yithStripePaymentsElement */








/**
 * Class that init and manages all interactions with Stripe Element
 */
var PaymentElement = /*#__PURE__*/function () {
  /**
   * Constructor method
   */
  function PaymentElement() {
    _classCallCheck(this, PaymentElement);
    /**
     * Flag enabled when we're on checkout or pay page
     */
    defineProperty_defineProperty(this, "isCheckout", void 0);
    /**
     * Flag enabled when we're on add payment method page
     */
    defineProperty_defineProperty(this, "isAddMethod", void 0);
    /**
     * jQuery element that reference target of embed
     */
    defineProperty_defineProperty(this, "$target", void 0);
    /**
     * Id for the embed target
     */
    defineProperty_defineProperty(this, "target", '#yith-stripe-payments-element');
    /**
     * Stores Stripe object once created
     */
    defineProperty_defineProperty(this, "stripe", void 0);
    /**
     * Store Elements object once created
     */
    defineProperty_defineProperty(this, "elements", void 0);
    /**
     * Store PaymentElement object once created
     */
    defineProperty_defineProperty(this, "paymentElement", void 0);
    // instance binding
    this.maybeInitElements = this.maybeInitElements.bind(this);
    this.updateElements = this.updateElements.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
    this.onError = this.onError.bind(this);
    this.onHashChange = this.onHashChange.bind(this);
    this.afterElementsSubmit = this.afterElementsSubmit.bind(this);
    this.clearPaymentMethod = this.clearPaymentMethod.bind(this);

    // init object
    this.init();
  }

  /* === INITIALIZATION METHOD === */

  /**
   * Init current object
   */
  _createClass(PaymentElement, [{
    key: "init",
    value: function init() {
      this.initStripe();
      this.maybeInitElements();
      this.initFormSubmit();
      this.initHashChange();
      $body.on('payment_method_selected', this.maybeInitElements);
    }

    /**
     * Init Stripe object
     */
  }, {
    key: "initStripe",
    value: function initStripe() {
      var _yithStripePaymentsEl = yithStripePaymentsElement,
        publicKey = _yithStripePaymentsEl.public_key,
        acctId = _yithStripePaymentsEl.account_id;
      if (!publicKey || !acctId) {
        return;
      }
      this.stripe = Stripe(publicKey, {
        stripeAccount: acctId
      });
    }

    /**
     * Init checkout handling
     */
  }, {
    key: "initFormSubmit",
    value: function initFormSubmit() {
      this.getForm().on("submit", this.onSubmit);
    }

    /**
     */
  }, {
    key: "initHashChange",
    value: function initHashChange() {
      window.addEventListener('hashchange', this.onHashChange);
      this.onHashChange();
    }

    /* === ELEMENTS HANDLING === */

    /**
     * Init Elements if currently selected payment method is Stripe Payments Element
     */
  }, {
    key: "maybeInitElements",
    value: function maybeInitElements() {
      var _yithStripePaymentsEl2 = yithStripePaymentsElement,
        slug = _yithStripePaymentsEl2.slug;
      var $selectedMethod = $('input[name="payment_method"]:checked'),
        selectedMethod = $selectedMethod.attr('id');
      if ("payment_method_".concat(slug) !== selectedMethod) {
        return;
      }

      // init elements.
      this.initElements();
    }

    /**
     * Init Elements given that conditions are matched
     *
     * @return {Promise<void>}
     */
  }, {
    key: "initElements",
    value: function () {
      var _initElements = _asyncToGenerator( /*#__PURE__*/regenerator_default().mark(function _callee() {
        return regenerator_default().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              if (this.stripe) {
                _context.next = 2;
                break;
              }
              return _context.abrupt("return");
            case 2:
              _context.t0 = this.stripe;
              _context.next = 5;
              return this.getElementsDetails();
            case 5:
              _context.t1 = _context.sent;
              this.elements = _context.t0.elements.call(_context.t0, _context.t1);
              this.paymentElement = this.elements.create('payment', {
                layout: yithStripePaymentsElement.layout
              }).on('change', this.clearPaymentMethod);
              this.mount();
            case 9:
            case "end":
              return _context.stop();
          }
        }, _callee, this);
      }));
      function initElements() {
        return _initElements.apply(this, arguments);
      }
      return initElements;
    }()
    /**
     * Update Elements with new Checkout details
     *
     * @return {Promise<void>}
     */
  }, {
    key: "updateElements",
    value: function () {
      var _updateElements = _asyncToGenerator( /*#__PURE__*/regenerator_default().mark(function _callee2() {
        return regenerator_default().wrap(function _callee2$(_context2) {
          while (1) switch (_context2.prev = _context2.next) {
            case 0:
              if (!(!this.stripe || !this.elements)) {
                _context2.next = 2;
                break;
              }
              return _context2.abrupt("return");
            case 2:
              _context2.t0 = this.elements;
              _context2.next = 5;
              return this.getElementsDetails();
            case 5:
              _context2.t1 = _context2.sent;
              _context2.t0.update.call(_context2.t0, _context2.t1);
              this.clearPaymentMethod();
              this.mount();
            case 9:
            case "end":
              return _context2.stop();
          }
        }, _callee2, this);
      }));
      function updateElements() {
        return _updateElements.apply(this, arguments);
      }
      return updateElements;
    }()
    /**
     * Submit Elements form and waits for response
     */
  }, {
    key: "submitElements",
    value: function submitElements() {
      if (!this.stripe || !this.elements) {
        return;
      }
      block(this.getForm());
      this.elements.submit().then(this.afterElementsSubmit)["catch"](this.onError);
    }

    /**
     * Handles successful responses from Elements submit
     */
  }, {
    key: "afterElementsSubmit",
    value: function afterElementsSubmit() {
      var _this = this;
      // otherwise proceed creating a payment method.
      this.stripe.createPaymentMethod({
        elements: this.elements,
        params: {
          billing_details: this.getBillingDetails()
        }
      }).then(function (_ref) {
        var error = _ref.error,
          paymentMethod = _ref.paymentMethod;
        unblock(_this.getForm());
        if (error) {
          _this.onError(error);
          return;
        }
        _this.appendPaymentMethod(paymentMethod);
        _this.getForm().trigger('submit');
      })["catch"](this.onError);
    }

    /**
     * Mount Elements on target node
     */
  }, {
    key: "mount",
    value: function mount() {
      this.paymentElement.mount(this.getTargetNode(true));
    }

    /* === FORM HANDLING === */

    /**
     * Returns container form
     *
     * @return {jQuery} Container form
     */
  }, {
    key: "getForm",
    value: function getForm() {
      var $target = this.getTarget();
      return $target.closest('form');
    }

    /**
     * Handles container form submit
     */
  }, {
    key: "onSubmit",
    value: function onSubmit() {
      if (!this.hasPaymentMethod()) {
        this.submitElements();
        return false;
      }
    }

    /**
     * Prints error messages relevant to customer
     *
     *
     * @param {Object} error Error describing current error.
     */
  }, {
    key: "onError",
    value: function onError(error) {
      var $form = this.getForm();
      unblock($form);

      // remove existing error messages.
      $form.find('.woocommerce-error').remove();

      // add new error message.
      var $errorMsg = $('<div/>', {
        "class": 'woocommerce-error',
        text: error.message
      });
      $form.prepend($errorMsg);

      // scroll to notice.
      $.scroll_to_notices($errorMsg);

      // trigger form fields validation.
      $form.find('.input-text, select, input:checkbox').trigger('validate').trigger('blur');
    }

    /* === PAYMENT METHOD HANDLING === */

    /**
     * Checks if current form already has a payment method or needs a new one
     *
     * @return {boolean} Whether form contains payment method or not
     */
  }, {
    key: "hasPaymentMethod",
    value: function hasPaymentMethod() {
      var $paymentMethod = this.getPaymentMethod();
      return $paymentMethod.length && !!$paymentMethod.val();
    }

    /**
     * Returns payment method Node
     *
     * @return {jQuery} jQuery node for the Payment Method hidden field
     */
  }, {
    key: "getPaymentMethod",
    value: function getPaymentMethod() {
      var $form = this.getForm();
      return $form.find("#yith-stripe-payments-".concat(yithStripePaymentsElement.slug, "-payment-method"));
    }

    /**
     * Set value for the Payment method input in the form.
     * If input does not exist, creates it and append it to the form
     *
     * @param {Object} paymentMethod Payment method object
     */
  }, {
    key: "appendPaymentMethod",
    value: function appendPaymentMethod(paymentMethod) {
      var $paymentMethod = this.getPaymentMethod();
      if (!$paymentMethod.length) {
        var $form = this.getForm();
        $paymentMethod = $('<input/>', {
          type: 'hidden',
          id: "yith-stripe-payments-".concat(yithStripePaymentsElement.slug, "-payment-method"),
          name: "yith_stripe_payments_".concat(yithStripePaymentsElement.slug, "_payment_method"),
          value: paymentMethod.id
        });
        $form.append($paymentMethod);
      } else {
        $paymentMethod.val(paymentMethod.id);
      }
    }

    /**
     * Clears payment method input, to make sure we don't accidentally process checkout with an outdated one
     */
  }, {
    key: "clearPaymentMethod",
    value: function clearPaymentMethod() {
      var $paymentMethod = this.getPaymentMethod();
      if (!$paymentMethod.length) {
        return;
      }
      $paymentMethod.val('');
    }

    /* === HANDLE NEXT ACTIONS === */

    /**
     * Reacts to hash change in the url
     */
  }, {
    key: "onHashChange",
    value: function () {
      var _onHashChange = _asyncToGenerator( /*#__PURE__*/regenerator_default().mark(function _callee3() {
        var _this2 = this;
        var hash, identifier, regex, matches, _matches, clientSecret, redirectUrl;
        return regenerator_default().wrap(function _callee3$(_context3) {
          while (1) switch (_context3.prev = _context3.next) {
            case 0:
              hash = window.location.hash;
              if (hash) {
                _context3.next = 3;
                break;
              }
              return _context3.abrupt("return");
            case 3:
              identifier = "yith-stripe-payments-".concat(yithStripePaymentsElement.slug), regex = new RegExp("^#".concat(identifier, "/confirm/([^/]+)/(.+)$")), matches = hash.match(regex);
              if (!(!matches || 3 < matches.length)) {
                _context3.next = 6;
                break;
              }
              return _context3.abrupt("return");
            case 6:
              window.location.hash = '';
              _matches = _slicedToArray(matches, 3), clientSecret = _matches[1], redirectUrl = _matches[2];
              this.stripe.handleNextAction({
                clientSecret: clientSecret
              }).then(function (result) {
                result.error && _this2.onError(result.error);
                result.paymentIntent && (window.location = redirectUrl);
              })["catch"](this.onError);
            case 9:
            case "end":
              return _context3.stop();
          }
        }, _callee3, this);
      }));
      function onHashChange() {
        return _onHashChange.apply(this, arguments);
      }
      return onHashChange;
    }()
    /* === GETTERS METHOD === */
    /**
     * Get an object with billing details about customer
     *
     * @return {Object} Billing object.
     */
  }, {
    key: "getBillingDetails",
    value: function getBillingDetails() {
      return {};
    }

    /**
     * Returns jQuery node to the embed target
     *
     * @param {boolean} refresh Whether to refresh target or not
     * @return {jQuery} jQuery node element.
     */
  }, {
    key: "getTarget",
    value: function getTarget(refresh) {
      if (!this.$target || refresh) {
        this.$target = $(this.target);
      }
      return this.$target;
    }

    /**
     * Returns DOM node to the embed target
     *
     * @param {boolean} refresh Whether to refresh target or not
     * @return {Element} DOM node element.
     */
  }, {
    key: "getTargetNode",
    value: function getTargetNode(refresh) {
      return this.getTarget(refresh).get(0);
    }

    /**
     * Returns an object with a set of options to be used for Elements init/update
     *
     * @return {Promise<object|boolean>} Settings object, or false on failure.
     */
  }, {
    key: "getElementsDetails",
    value: function () {
      var _getElementsDetails = _asyncToGenerator( /*#__PURE__*/regenerator_default().mark(function _callee4() {
        return regenerator_default().wrap(function _callee4$(_context4) {
          while (1) switch (_context4.prev = _context4.next) {
            case 0:
              return _context4.abrupt("return", {
                mode: 'setup',
                currency: yithStripePaymentsElement.currency
              });
            case 1:
            case "end":
              return _context4.stop();
          }
        }, _callee4);
      }));
      function getElementsDetails() {
        return _getElementsDetails.apply(this, arguments);
      }
      return getElementsDetails;
    }()
  }]);
  return PaymentElement;
}();

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
;// CONCATENATED MODULE: ./assets/js/src/modules/PaymentElementCheckout.js


/* globals jQuery Stripe yithStripePayments yithStripePaymentsElement */










function PaymentElementCheckout_ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function PaymentElementCheckout_objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? PaymentElementCheckout_ownKeys(Object(source), !0).forEach(function (key) { defineProperty_defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : PaymentElementCheckout_ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }
function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }




/**
 * Class that init and manages all interactions with Stripe Element on Checkout
 */
var PaymentElementCheckout = /*#__PURE__*/function (_PaymentElement) {
  _inherits(PaymentElementCheckout, _PaymentElement);
  var _super = _createSuper(PaymentElementCheckout);
  function PaymentElementCheckout() {
    var _this;
    _classCallCheck(this, PaymentElementCheckout);
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    _this = _super.call.apply(_super, [this].concat(args));
    defineProperty_defineProperty(_assertThisInitialized(_this), "isCheckout", true);
    return _this;
  }
  _createClass(PaymentElementCheckout, [{
    key: "init",
    value: /* === INITIALIZATION METHOD === */
    /**
     * Init current object
     */
    function init() {
      _get(_getPrototypeOf(PaymentElementCheckout.prototype), "init", this).call(this);
      $body.on('updated_checkout', this.updateElements);
      $body.on('checkout_error', this.clearPaymentMethod);
    }

    /**
     * Init checkout handling
     */
  }, {
    key: "initFormSubmit",
    value: function initFormSubmit() {
      this.getForm().on("checkout_place_order_".concat(yithStripePaymentsElement.slug), this.onSubmit).on('change', ':input', this.clearPaymentMethod);
    }

    /**
     * Checks if current form already has a payment method or needs a new one
     *
     * @return {boolean} Whether form contains payment method or not
     */
  }, {
    key: "hasPaymentMethod",
    value: function hasPaymentMethod() {
      return _get(_getPrototypeOf(PaymentElementCheckout.prototype), "hasPaymentMethod", this).call(this) || !!this.getSelectedToken();
    }

    /* === GETTERS METHOD === */

    /**
     * Get an object with billing details about customer
     *
     * @return {Object} Billing object.
     */
  }, {
    key: "getBillingDetails",
    value: function getBillingDetails() {
      var $form = this.getForm(),
        $billingFields = $form.find('[name^=billing]');
      if (!$billingFields.length) {
        return {};
      }
      var address = {};
      $billingFields.each(function (i, v) {
        var $field = $(v),
          name = $field.attr('name').replace('billing_', '');
        address[name] = $field.val();
      });
      var name = "".concat(address.first_name, " ").concat(address.last_name);
      return {
        name: name,
        email: address === null || address === void 0 ? void 0 : address.email,
        phone: address === null || address === void 0 ? void 0 : address.phone,
        address: {
          country: address === null || address === void 0 ? void 0 : address.country,
          city: address === null || address === void 0 ? void 0 : address.city,
          line1: address === null || address === void 0 ? void 0 : address.address_1,
          line2: address === null || address === void 0 ? void 0 : address.address_2,
          postal_code: address === null || address === void 0 ? void 0 : address.postcode,
          state: address.state
        }
      };
    }

    /**
     * Returns selected payment method from existing ones, if any
     *
     * @return {string|boolean} Selected token among existing ones, or false if no selection is made (or if no token exists)
     */
  }, {
    key: "getSelectedToken",
    value: function getSelectedToken() {
      var $form = this.getForm(),
        $tokens = $form.find("[name=\"wc-".concat(yithStripePaymentsElement.slug, "-payment-token\"]")),
        $selectedToken = $tokens.filter(':selected');
      if (!$selectedToken.length) {
        return false;
      }
      var selectedToken = $selectedToken.val();
      if (!selectedToken) {
        return false;
      }
      return selectedToken;
    }

    /**
     * Returns an object with a set of options to be used for Elements init/update
     *
     * @return {Promise<object|boolean>} Settings object, or false on failure.
     */
  }, {
    key: "getElementsDetails",
    value: function () {
      var _getElementsDetails = _asyncToGenerator( /*#__PURE__*/regenerator_default().mark(function _callee() {
        var amount, currency, secret, mode, checkoutDetails, details;
        return regenerator_default().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              _context.prev = 0;
              _context.next = 3;
              return this.getCheckoutDetails();
            case 3:
              checkoutDetails = _context.sent;
              amount = checkoutDetails === null || checkoutDetails === void 0 ? void 0 : checkoutDetails.total;
              currency = checkoutDetails === null || checkoutDetails === void 0 ? void 0 : checkoutDetails.currency;
              secret = checkoutDetails === null || checkoutDetails === void 0 ? void 0 : checkoutDetails.secret;
              mode = amount ? 'payment' : 'setup';
              _context.next = 13;
              break;
            case 10:
              _context.prev = 10;
              _context.t0 = _context["catch"](0);
              return _context.abrupt("return", false);
            case 13:
              details = {
                paymentMethodCreation: 'manual',
                appearance: yithStripePaymentsElement.appearance
              };
              if (secret) {
                details.clientSecret = secret;
              } else {
                details = PaymentElementCheckout_objectSpread(PaymentElementCheckout_objectSpread({}, details), {}, {
                  mode: mode,
                  currency: currency,
                  captureMethod: yithStripePaymentsElement.capture_method
                });
                if ('payment' === mode) {
                  details.amount = amount;
                }
              }
              if (!!yithStripePaymentsElement.tokenization) {
                details.setupFutureUsage = 'on_session';
              }
              return _context.abrupt("return", details);
            case 17:
            case "end":
              return _context.stop();
          }
        }, _callee, this, [[0, 10]]);
      }));
      function getElementsDetails() {
        return _getElementsDetails.apply(this, arguments);
      }
      return getElementsDetails;
    }()
    /**
     * Retrieves fresh checkout details via AJAX
     *
     * @return {Promise<object>} JSON object retrieve form AJAX call, containing details about the checkout, such as amount and currency
     */
  }, {
    key: "getCheckoutDetails",
    value: function () {
      var _getCheckoutDetails = _asyncToGenerator( /*#__PURE__*/regenerator_default().mark(function _callee2() {
        return regenerator_default().wrap(function _callee2$(_context2) {
          while (1) switch (_context2.prev = _context2.next) {
            case 0:
              _context2.next = 2;
              return ajax.get.call(null, 'get_checkout_details', 'get_checkout_details', {
                gateway: 'element'
              });
            case 2:
              return _context2.abrupt("return", _context2.sent);
            case 3:
            case "end":
              return _context2.stop();
          }
        }, _callee2);
      }));
      function getCheckoutDetails() {
        return _getCheckoutDetails.apply(this, arguments);
      }
      return getCheckoutDetails;
    }()
  }]);
  return PaymentElementCheckout;
}(PaymentElement);

;// CONCATENATED MODULE: ./assets/js/src/modules/PaymentElementCards.js


/* globals jQuery Stripe yithStripePayments yithStripePaymentsElement */









function PaymentElementCards_createSuper(Derived) { var hasNativeReflectConstruct = PaymentElementCards_isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }
function PaymentElementCards_isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }


/**
 * Class that init and manages all interactions with Stripe Element on Checkout
 */
var PaymentElementCards = /*#__PURE__*/function (_PaymentElement) {
  _inherits(PaymentElementCards, _PaymentElement);
  var _super = PaymentElementCards_createSuper(PaymentElementCards);
  function PaymentElementCards() {
    var _this;
    _classCallCheck(this, PaymentElementCards);
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    _this = _super.call.apply(_super, [this].concat(args));
    defineProperty_defineProperty(_assertThisInitialized(_this), "isAddMethod", true);
    return _this;
  }
  _createClass(PaymentElementCards, [{
    key: "getElementsDetails",
    value:
    /* === INITIALIZATION METHOD === */
    /**
     * Returns an object with a set of options to be used for Elements init/update
     *
     * @return {Promise<object|boolean>} Settings object, or false on failure.
     */
    function () {
      var _getElementsDetails = _asyncToGenerator( /*#__PURE__*/regenerator_default().mark(function _callee() {
        return regenerator_default().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              return _context.abrupt("return", {
                mode: 'setup',
                setupFutureUsage: 'on_session',
                currency: yithStripePaymentsElement.currency,
                appearance: yithStripePaymentsElement.appearance
              });
            case 1:
            case "end":
              return _context.stop();
          }
        }, _callee);
      }));
      function getElementsDetails() {
        return _getElementsDetails.apply(this, arguments);
      }
      return getElementsDetails;
    }()
  }]);
  return PaymentElementCards;
}(PaymentElement);

;// CONCATENATED MODULE: ./assets/js/src/element.js


/* global wc jQuery Stripe */


jQuery(function ($) {
  var $elementsContainer = $('.payment_method_element'),
    $form = $elementsContainer.closest('form');
  if (!$form.length) {
    return;
  }
  if ($form.is('#add_payment_method')) {
    new PaymentElementCards();
  } else if ($form.is('.checkout') || $form.is('#order_review')) {
    new PaymentElementCheckout();
  }
});
})();

var __webpack_export_target__ = window;
for(var i in __webpack_exports__) __webpack_export_target__[i] = __webpack_exports__[i];
if(__webpack_exports__.__esModule) Object.defineProperty(__webpack_export_target__, "__esModule", { value: true });
/******/ })()
;
//# sourceMappingURL=yith-stripe-payments-element.bundle.js.map