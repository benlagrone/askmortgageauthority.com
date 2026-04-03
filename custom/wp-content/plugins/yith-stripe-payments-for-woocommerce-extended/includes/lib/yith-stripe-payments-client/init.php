<?php
/**
 * YITH Stripe Client
 *
 * Library that implements Client to call YITH Stripe Server
 * You can read more on the usage of this library in README.md file
 *
 * @package YITH\StripeClient
 * @version 1.0.0
 */

namespace YITH\StripeClient;

defined( 'ABSPATH' ) || exit;

defined( 'YITH_STRIPE_CLIENT_VERSION' ) || define( 'YITH_STRIPE_CLIENT_VERSION', '0.1.0' );
defined( 'YITH_STRIPE_CLIENT_DIR' ) || define( 'YITH_STRIPE_CLIENT_DIR', __DIR__ . '/' );
defined( 'YITH_STRIPE_CLIENT_INC' ) || define( 'YITH_STRIPE_CLIENT_INC', YITH_STRIPE_CLIENT_DIR . 'includes/' );

// register text domain.
load_plugin_textdomain( 'yith-stripe-client', false, plugin_basename( YITH_STRIPE_CLIENT_DIR . 'languages/' ) );

// require composer libraries.
require_once 'vendor/autoload.php';

// include required resources.
require_once 'includes/class-autoloader.php';

Main::instance();
