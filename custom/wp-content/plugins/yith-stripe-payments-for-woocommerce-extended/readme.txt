=== YITH Stripe Payments for WooCommerce ===

Contributors: yithemes
Tags:  yith, stripe, elements, payments, credit card, klarna, ach, direct bank, apple pay, google pay
Requires at least: 6.5
Tested up to: 6.8
Stable tag: 1.3.0
License: GPLv2 or later

== Changelog ==

= 1.4.0 - Released on 10 April 2025 =

* New: support for WordPress 6.8
* Update: YITH plugin framework
* Update: language files

= 1.3.0 - Released on 24 March 2025 =

* New: support for WooCommerce 9.8
* Update: YITH plugin framework
* Update: language files

= 1.2.0 - Released on 3 March 2025 =

* New: support for WooCommerce 9.7
* Update: YITH plugin framework
* Update: language files

= 1.1.0 - Released on 5 December 2024 =

* New: support for WordPress 6.7
* New: support for WooCommerce 9.5
* Update: YITH plugin framework
* Update: language files

= 1.0.8 - Released on 29 October 2024 =

* Update: YITH plugin framework
* Update: language files

= 1.0.7 - Released on 28 October 2024 =

* New: support for WordPress 6.6
* New: support for WooCommerce 9.3
* Update: YITH plugin framework
* Update: language files
* Fix: PHP 8.2 deprecated errors with dynamic property declaration

= 1.0.6 - Released on 27 March 2024 =

* New: support for WordPress 6.5
* New: support for WooCommerce 8.7
* New: Stripe account remains connected even when the brand plugin is disabled
* Update: YITH plugin framework
* Update: language files
* Fix: passing a valid callback as rest route schema in rest-api registration
* Fix: removing hash from window location only if it's related to the payment element
* Tweak: updated Stripe Element gateway description

= 1.0.5 - Released on 19 February 2024 =

* New: support for WooCommerce 8.6
* New: added payment method domain status and its payment method statuses to connection status information
* New: Apple Pay merchant ID domain association file
* Update: YITH plugin framework
* Update: language files
* Fix: improved the behavior of enable toggle button in payment methods list
* Tweak: adding and flushing rewrite rules only when needed
* Tweak: updated Stripe Element gateway description
* Tweak: added payment method icons next to the Stripe Element title to WooCommerce settings payment methods list
* Tweak: sort plugin gateways as first payment methods in the list

= 1.0.4 - Released on 2 February 2024 =

* New: abstract class for NewFold plugins integrations
* Update: YITH plugin framework
* Update: language files
* Fix: avoid errors in ajax request function and multiple-request handling improvements

= 1.0.3 - Released on 31 January 2024 =

* New: added HostGator integration
* Update: YITH plugin framework
* Update: language files
* Fix: singleton issues in integration classes
* Tweak: loading integrations class only if needed
* Tweak: avoding multiple ajax call for the same request

= 1.0.2 - Released on 12 January 2024 =

* New: support for WooCommerce 8.5
* New: support for WooCommerce HPOS feature
* New: WooCommerce tool to clear plugin cache
* New: Stripe onboarding integration in BlueHost panel
* Update: YITH plugin framework
* Update: language files
* Fix: prevent multiple enqueuing scripts in Payment Element gateway
* Tweak: update Stripe Client environment attribute when environment option changes
* Tweak: Tweak: revoke account even if an error code is returned by the server
* Tweak: changed label for Disconnect button on onboarding details widget
* Tweak: added brand condition to the availability checks in the Gateway abstract class
* Tweak: removed 'bluehost' as a default brand
* Tweak: added warning notice when using a non-valid brand
* Tweak: improved BH integration performance
* Tweak: improved BH onboarding checking the connection_status option and updating the integration according to its value
* Tweak: check registered brands and use BlueHost as a default
* Tweak: update Onboarding status in BlueHost panel just when details are submitted
* Dev: print log only if they have a non-empty message

= 1.0.1 - Released on 30 November 2023 =

* Update: YITH plugin framework
* Dev: added the Hiive Auto Updater

= 1.0.0 - Released on 28 November 2023 =

* Initial release
