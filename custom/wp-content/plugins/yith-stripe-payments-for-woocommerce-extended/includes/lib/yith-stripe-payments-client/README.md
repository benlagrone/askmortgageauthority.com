# YITH Stripe Client

**IMPORTANT:** this extension requires WordPress to correctly work

This library was created an utility to easily connect and interact with [YITH Stripe Middleware Server](https://bitbucket.org/yithemes/yith-stripe-payments-middleware/src/master/)

Library performs RESTful API calls to the server using either WP_HTTP or Guzzle clients to execute calls and process responses.<br/>
By default it will use WP_HTTP, but user can control processor using `yith_stripe_client_processor` filter

## Server environment 

Library will check `WP_ENV` constant to know if it is running in test mode or not.
Depending on current environment, library will either try to contact remote server, or a local instance of it.<br/>
Consult server README.md to find more info on how to run server locally

## Installation

You can include this library in your project as a GIT submodule, by running

```bash
git submodule add https://bitbucket.org/yithemes/yith-stripe-payments-client $DESTINATION
cd $DESTINATION
composer update
```

After adding the library in your project, you can include it by simply requiring init.php file from the installation root.
Autoloader will do the rest, dynamically including files when needed :)

## Usage

Library is model based, so in order to call the proper method on the static class representing the object you want to operate on.
For example, if you want to create an account, you'll need to call

```php
use YITH\StripeClient\Models\Account;
Account::create( [
    // data for the account...
] );
```

method, passing proper data as parameters.

Library use namespace to let you easily reference classes bundled with this software.
Base namespace is `YITH\StripeClient`, where you can find `Client` static class.
Anyway most interactions will occur using models contained in the namespace `YITH\StripeClient\Models`

## Configuration

You can configure client to use specific options by using either `maybe_init()` or `set()` methods of the client class

```php
use YITH\StripeClient\Client
Client::maybe_init( [
    'env' => 'test'
] );
Client::set( 'user-agent', 'MyCustomApplication/1.0.0' );
```
