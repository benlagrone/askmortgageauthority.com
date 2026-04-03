/**
 * Some useful links
 * Card networks supported by your site and your gateway
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
 * @todo confirm card networks supported by your site and gateway
 * 
 * Card authentication methods supported by your site and your gateway
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
 * @todo confirm your processor supports Android device tokens for your
 * supported card networks
 * Identify your gateway and your site's gateway merchant identifier
 *
 * The Google Pay API response will return an encrypted payment method capable
 * of being charged by a supported gateway after payer authorization
 *
 * @todo check with your gateway on the parameters to pass
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#gateway|PaymentMethodTokenizationSpecification}
 
 * Describe your site's support for the CARD payment method and its required
 * fields
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
 *
 * Describe your site's support for the CARD payment method including optional
 * fields
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
 */

/* Starting Listener */
window.addEventListener('load', () => {
	(async () => {
		maybeShowButton();
	})();
	
});

async function maybeShowButton() {
	
	let check_container = document.getElementById('googlepay-container');

	if ( '' === yith_ppwc_google_pay.context || ! check_container ) { return; }

	/* Get Google Pay Paypal configuration */
	const googlepayCfg = await paypal.Googlepay().config();
	const {
		isEligible,
		countryCode,
		merchantInfo,
		allowedPaymentMethods,
	} = googlepayCfg;

	if ( ! isEligible ) {
		throw new Error("googlepay is not eligible");
	}

	const gpbtn = new GooglePayButton(googlepayCfg);
	gpbtn.init();
}

class GooglePayButton {

	constructor(googlepayCfg) {
		this.config = googlepayCfg;
		this.paymentsClient = null;
		this.shippingCountries = yith_ppwc_google_pay.countries;
		this.context = yith_ppwc_google_pay.context;
		this.environment = yith_ppwc_google_pay.environment;
		this.buttonColor = yith_ppwc_google_pay.buttonColor;
		this.buttonType = yith_ppwc_google_pay.buttonType;
		this.buttonSizeMode = yith_ppwc_google_pay.buttonSizeMode;
		this.buttonLocale = yith_ppwc_google_pay.buttonLocale;
		this.form = '';
		this.fundingSource = yith_ppwc_google_pay.fundingSource;
		this.formRequestType = '';
		this.baseRequest = {
			apiVersion: 2,
			apiVersionMinor: 0
		}; // @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|apiVersion in PaymentDataRequest}
	}
	/* init Events Handlers */
	initEvents() {
		jQuery(document.body).on('updated_cart_totals updated_checkout update_shipping_method', function() {
			maybeShowButton();
		});
	
		// Handle single product page.
		jQuery( 'form.variations_form' ).on( 'show_variation', function( ev, variation, purchasable ) {
			let b = jQuery('.yith-ppwc-button-container').find('#googlepay-container');
			if ( purchasable ) {
				b.show();
			} else {
				b.hide();
			}
		} );
		jQuery( 'form.variations_form' ).on( 'hide_variation', function( ev, variation, purchasable ) {
			let b = jQuery('.yith-ppwc-button-container').find('#googlepay-container');
			b.hide();
		} );
	}
	/* init the button */
	init () {
		this.paymentsClient = this.getGooglePaymentsClient();
	
		this.paymentsClient.isReadyToPay(this.getGoogleIsReadyToPayRequest(this.config.allowedPaymentMethods, this.config))
			.then((response) => {
				if (response.result) {
					this.renderButton();
					this.initEvents();
				}
			})
			.catch((err) => {
				// show error in developer console for debugging
				console.error('yith_ppwc_google_pay init', err);
			});
	}
	/**
	 * Return an active PaymentsClient or initialize
	 *
	 * @see {@link https://developers.google.com/pay/api/web/reference/client#PaymentsClient|PaymentsClient constructor}
	 * @returns {google.payments.api.PaymentsClient} Google Pay API client
	 */
	getGooglePaymentsClient() {
		if (this.paymentsClient === null) {

			const callbacks = {
				onPaymentAuthorized: this.onPaymentAuthorized.bind(this),
				onPaymentDataChanged: this.onPaymentDataChanged.bind(this),
			}

			this.paymentsClient = new google.payments.api.PaymentsClient({
				environment: 'sandbox' === this.environment ? 'TEST' : 'PRODUCTION',
				//merchantInfo: this.config.merchantInfo,
				paymentDataCallbacks: callbacks
			});
		}
		return this.paymentsClient;
	}
	/**
	 * Configure your site's support for payment methods supported by the Google Pay API.
	 *
	 * Each member of allowedPaymentMethods should contain only the required fields,
	 * allowing reuse of this base request when determining a viewer's ability
	 * to pay and later requesting a supported payment method
	 *
	 * @returns {object} Google Pay API version, payment methods supported by the site
	 */
	getGoogleIsReadyToPayRequest( allowedPaymentMethods, baseRequest ) {
		return Object.assign({},
			baseRequest, {
			allowedPaymentMethods: allowedPaymentMethods // get from baseRequest
		}
		);
	}
	/**
	 * Add a Google Pay purchase button
	 *
	 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#ButtonOptions|Button options}
	 * @see {@link https://developers.google.com/pay/api/web/guides/brand-guidelines|Google Brand Guidelines}
	 */
	renderButton() {
		const button = this.paymentsClient.createButton({
			onClick: this.onGooglePaymentButtonClicked.bind(this),
			allowedPaymentMethods: this.config.allowedPaymentMethods,
			buttonColor: this.buttonColor,
			buttonType: this.buttonType,
			buttonSizeMode: this.buttonSizeMode,
			buttonLocale: 'browser' === this.buttonLocale ? '': this.buttonLocale,
		});

		let el = document.getElementById('googlepay-container');
		el.innerHTML = '';
		el.appendChild(button);
	}

	/**
	 * On google button event
	 */
	async onGooglePaymentButtonClicked() {
		var valid = '';

		if ( 'cart' === this.context || 'checkout' === this.context ) {
			valid = {validate: true};
		}

		if ( 'product' === this.context ) {
			let b = document.getElementById('googlepay-container');
			this.form = jQuery(b).closest( '.product.type-product' ).find( 'form.cart' );
			if ( this.form.length ) {
				valid = await this.validate_cart();
			}
		}

		if ( valid?.validate ) {
			const paymentDataRequest = await this.getGooglePaymentDataRequest();
			this.paymentsClient.loadPaymentData(paymentDataRequest).then( response => {
			}).catch((err) => {
				if ( err?.statusCode === 'CANCELED' ) {
					if ( 'product' === this.context ) {
						this.maybe_clean_session();
					}
				}
			});
		}
	}

	async maybe_clean_session() {
		var body = [];
		body.push({name:'request', value: 'maybe_clean_session'});
		this.blockFormRequest();
		const res = await fetch(yith_ppwc_frontend.ajaxUrl, {
			method: 'POST',
			headers: {
				'content-type': 'application/x-www-form-urlencoded'
			},
			credentials: 'same-origin',
			body: this.formatRequestBody( body, 'googlepay' ),
		});

		this.unblockFormRequest();

	}
	/**
	 * Validate Cart Form
	 * 
	 * @returns Promise
	 */
	validate_cart() {
		return new Promise( async(resolve, reject) => {
			try {
				var body = [];
				if ( this.form ) {	
					body = this.form.serializeArray();
					body.push({name:'request', value: 'validate_product_cart'});
					if ( 'product' === this.context ) {
						body.push( {name: 'is-yith-ppwc-action', value: 'yes'} );
						let addToCart = this.form.find( 'button[name="add-to-cart"]' );
						if ( addToCart.length ) {
							body.push({name: 'add-to-cart', value: addToCart.val()});
						}
					}
					this.blockFormRequest();
					const res = await fetch(yith_ppwc_frontend.ajaxUrl, {
						method: 'POST',
						headers: {
							'content-type': 'application/x-www-form-urlencoded'
						},
						credentials: 'same-origin',
						body: this.formatRequestBody( body, this.context ),
					});
					
					const data = await res.json();
					if ( data && 'failure' === data.result ) {
						this.handleRequestError( data, 'cart' );
						return resolve(
							{ validate: false }
						);
					}

					this.unblockFormRequest();

					return resolve(
						{ validate: true }
					);
				} else {
					return resolve(
						{ validate: false }
					);
				}
			} catch(err) {
				console.log('yith_ppwc_google_pay validate_cart', err);
				reject();
			}

		});
	}

	/**
	 * Configure support for the Google Pay API
	 *
	 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|PaymentDataRequest}
	 * @returns {object} PaymentDataRequest fields
	 */
	async getGooglePaymentDataRequest() {
		const paymentDataRequest = Object.assign({}, this.baseRequest);
		paymentDataRequest.allowedPaymentMethods = this.config.allowedPaymentMethods;
		paymentDataRequest.transactionInfo = await this.getGoogleTransactionInfo();
		paymentDataRequest.merchantInfo = this.config.merchantInfo;
	
		paymentDataRequest.callbackIntents = ["SHIPPING_ADDRESS",  "SHIPPING_OPTION", "PAYMENT_AUTHORIZATION"];
		paymentDataRequest.shippingAddressRequired = true;
		paymentDataRequest.shippingAddressParameters = this.shippingAddressParameters();
		paymentDataRequest.shippingOptionRequired = true;
		paymentDataRequest.emailRequired = true;

		this.logger('paymentDataRequest',paymentDataRequest);
		return paymentDataRequest;
	}

	/** Shipping management **/
	shippingAddressParameters() {
		return {
            allowedCountryCodes: this.shippingCountries,
            phoneNumberRequired: true
        };
	}
    onPaymentDataChanged(paymentData) {
        this.logger('onPaymentDataChanged', paymentData);

        return new Promise(async (resolve, reject) => {
            let paymentDataRequestUpdate = {};

            const updatedData = await this.updatePaymentData( paymentData );
			this.logger('onPaymentDataChanged:updatedData', updatedData);
            const transactionInfo = await this.getGoogleTransactionInfo();

            updatedData.country_code = transactionInfo.countryCode;
            updatedData.currency_code = transactionInfo.currencyCode;
            updatedData.total_str = transactionInfo.totalPrice;

            // Handle unserviceable address.
            if(!updatedData.shipping_options || !updatedData.shipping_options.shippingOptions.length) {
                paymentDataRequestUpdate.error = this.unserviceableShippingAddressError();
                resolve(paymentDataRequestUpdate);
                return;
            }

            switch (paymentData.callbackTrigger) {
                case 'INITIALIZE':
                case 'SHIPPING_ADDRESS':
                    paymentDataRequestUpdate.newShippingOptionParameters = updatedData.shipping_options;
                    paymentDataRequestUpdate.newTransactionInfo = this.calculateNewTransactionInfo(updatedData);
                    break;
                case 'SHIPPING_OPTION':
                    paymentDataRequestUpdate.newTransactionInfo = this.calculateNewTransactionInfo(updatedData);
                    break;
            }

            resolve(paymentDataRequestUpdate);
        });
    }
    unserviceableShippingAddressError() {
        return {
            reason: "SHIPPING_ADDRESS_UNSERVICEABLE",
            message: "Cannot ship to the selected address",
            intent: "SHIPPING_ADDRESS"
        };
    }
	calculateNewTransactionInfo(updatedData) {
        return {
            countryCode: updatedData.country_code,
            currencyCode: updatedData.currency_code,
            totalPriceStatus: 'FINAL',
            totalPrice: updatedData.total_str
        };
    }
	updatePaymentData(paymentData) {
		this.logger( 'updatePaymentData' );
		return new Promise((resolve, reject) => {
				try{
				var body = [];
				body.push({name:'request', value: 'update_payment_data'});
				body.push({name: 'paymentData', value: JSON.stringify(paymentData)})
				this.logger( 'updatePaymentData fetch' );
				fetch(
					yith_ppwc_frontend.ajaxUrl,
					{
						method: 'POST',
						headers: {
							'content-type': 'application/x-www-form-urlencoded'
						},
						credentials: 'same-origin',
						body: this.formatRequestBody(body,'googlepay')
					}
				)
					.then(result => result.json())
					.then(result => {
						if (!result.success) {
							return;
						}

						resolve(result.data);
					});
			} catch (err) {
				console.error('yith_ppwc_google_pay updatePaymentData', err);
				reject();
			}
		});
	}
	/**
	 * Provide Google Pay API with a payment amount, currency, and amount status
	 *
	 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#TransactionInfo|TransactionInfo}
	 * @returns {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
	 */
	getGoogleTransactionInfo() {
		return new Promise( async( resolve, reject ) => {
			try{
				var body = [];
			
				body.push({name:'request', value: 'cart_info'});
				
				await fetch(yith_ppwc_frontend.ajaxUrl, {
					method: 'POST',
					headers: {
						'content-type': 'application/x-www-form-urlencoded'
					},
					credentials: 'same-origin',
					body: this.formatRequestBody(body,this.context)
				})
				.then( result => result.json())
				.then( result => {
					if (!result.success) {
                        return;
                    }

					return resolve(
						{
							countryCode: result.data.countryCode,
							currencyCode: result.data.currencyCode,
							totalPriceStatus: "FINAL",
							totalPrice: result.data.totalPrice,
							totalPriceLabel: result.data.totalPriceLabel,
						}
					)
				});
			}
			catch (err) {
				console.error( 'yith_ppwc_google_pay getGoogleTransactionInfo', err);
				reject();
			}
		});
	}

	/* Payment Process */
	onPaymentAuthorized(paymentData) {
		this.logger('payment authorized', paymentData);
		this.blockFormRequest();
		return this.processPayment(paymentData);
	}
	/**
	 * Process payment data returned by the Google Pay API
	 *
	 * @param {object} paymentData response from Google Pay API after user approves payment
	 * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentData|PaymentData object reference}
	 */
	async processPayment(paymentData) {
		return new Promise(async (resolve, reject) => {

			// show returned data in developer console for debugging
			this.logger('ProcessPayment', paymentData);
			try {
				let orderId = await this.createOrder(this, paymentData);
				this.logger('Order created ' + orderId);
				
				const confirmOrderResponse = await paypal.Googlepay().confirmOrder({
                    orderId: orderId,
                    paymentMethodData: paymentData.paymentMethodData
                });

                this.logger('processPayment: confirmOrder', confirmOrderResponse);

				/** Capture the Order on the Server */
                if (confirmOrderResponse.status === "APPROVED") {

					let approveFailed = false;
                    await this.approveOrder(
						this,
						{
                        orderID: orderId
                    }, { // actions mock object.
                        restart: () => new Promise((resolve, reject) => {
                            approveFailed = true;
                            resolve();
                        }),
                        order: {
                            get: () => new Promise((resolve, reject) => {
                                resolve(null);
                            })
                        }
                    });

                    if (!approveFailed) {
                        resolve(this.processPaymentResponse('SUCCESS'));
                    } else {
                        resolve(this.processPaymentResponse('ERROR', 'PAYMENT_AUTHORIZATION', 'FAILED TO APPROVE'));
                    }

				} else {
					resolve(this.processPaymentResponse('ERROR', 'PAYMENT_AUTHORIZATION', 'TRANSACTION FAILED'));
				}
			}
			catch (err){
				resolve(this.processPaymentResponse('ERROR', 'PAYMENT_AUTHORIZATION', err.message));
			}
		});
	}

	/**
	 * Create Order
	 */
	async createOrder(config, paymentData, data, action) {
		this.logger('creating order');
		return fetch( yith_ppwc_frontend.ajaxUrl, {
			method: 'POST',
			headers: {
				'content-type': 'application/x-www-form-urlencoded'
			},
			body: this.formatRequestBody( [
				{name: 'request', value: 'create_order'},
				{name: 'checkoutRequest', value: this.context},
				{name: 'orderID', value: yith_ppwc_google_pay.orderId},
				{name: 'fundingSource', value: config.fundingSource},
				{name: 'email', value: paymentData.email},
				{name: 'shippingFields', value: JSON.stringify(paymentData.shippingAddress)}
			], 'googlepay' )
		} )
			.then( function( res ) {
				return res.json();
			} )
			.then( function( data ) {
				return data.id; // Use the same key name for order ID on the client and server
			} );
	}

	async approveOrder(config, data, actions) {
		this.logger('approving order');
		if ( data && data.orderID ) {

			fetch( yith_ppwc_frontend.ajaxUrl, {
				method: 'POST',
				headers: {
					'content-type': 'application/x-www-form-urlencoded'
				},
				body: this.formatRequestBody( [
					{name: 'request', value: 'approve_order'},
					{name: 'orderID', value: data.orderID},
					{name: 'checkoutRequest', value: this.context},
					{name: 'fundingSource', value: config.fundingSource}
				], 'googlepay' ),
			} )
				.then( function( res ) {
					return res.json();
				} )
				.then( function( json ) {
					if ( json ) {

						if ( json.result && 'failure' === json.result ) {
							window.location.reload();
						}

						if ( json.redirect ) {
							window.location.href = json.redirect;
						}
					}

				} );
		}
	}

    processPaymentResponse(state, intent = null, message = null) {
        let response = {
            transactionState: state,
        }

        if (intent || message) {
            response.error = {
                intent: intent,
                message: message,
            }
        }

        this.logger('processPaymentResponse', response, this.context);

        return response;
    }

	formatRequestBody( body, flow ) {
		var formatted = [];

		// add security nonce
		body.push(
			{name: 'security', value: yith_ppwc_frontend.ajaxNonce},
		);

		if ( '' !== flow ) {
			body.push(
				{name: 'flow', value: flow}
			);
		}

		body.push(
			{name: 'funding', value: this.fundingSource}
		);

		jQuery.each( body, function( index, item ) {
			formatted.push( item.name + '=' + item.value );
		} );

		return formatted.join( '&' );
	}

	handleRequestError( data, formRequestType ) {

		if ( data && data.reload ) {
			window.location.reload();
		} else if ( data && data.redirect ) {
			window.location.href = data.redirect;
		} else {

			var error_messages 	= ( data && data.messages.length ) ? data.messages : yith_ppwc_frontend.errorMessage;

			if ( 'checkout' === formRequestType ) {
				jQuery( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove();
				jQuery( 'form.checkout' ).prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_messages + '</div>' );

				jQuery( document.body ).trigger( 'checkout_error', [ error_messages ] );
				this.form.find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).blur();
			} else {
				jQuery( '.woocommerce-notices-wrapper' ).first().html( error_messages );
			}

			if ( typeof jQuery.scroll_to_notices != 'undefined' ) {
				jQuery.scroll_to_notices( ( 'checkout' === formRequestType ? jQuery('.woocommerce-NoticeGroup-checkout') : jQuery( '.woocommerce-notices-wrapper' ).first() ) );
			}

			this.unblockFormRequest();
		}

		return false;
	}
	blockFormRequest() {

		let toblock = [];
		switch ( this.context ) {
			case 'cart':
				toblock.push( jQuery('.woocommerce-cart .cart_totals') );
				toblock.push( jQuery('.woocommerce-cart .woocommerce-cart-form') );
				break;
			case 'checkout':
				toblock.push( jQuery('form.woocommerce-checkout'));
				break;
			case 'product':
				toblock.push( jQuery( 'form.cart' ) );
		}

		toblock.push( jQuery('.yith-ppwc-button-container') );

		toblock.forEach( (f) => {
			f.addClass('processing').block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );
		})
	}
	unblockFormRequest() {
		let toUnblock = [];
		switch ( this.context ) {
			case 'cart':
				toUnblock.push( jQuery('.woocommerce-cart .cart_totals'));
				toUnblock.push( jQuery('.woocommerce-cart .woocommerce-cart-form'));
				break;
			case 'checkout':
				toUnblock.push( jQuery('form.woocommerce-checkout'));
				break;
			case 'product':
				toUnblock.push( jQuery( 'form.cart' ) );
		}

		toUnblock.push( jQuery('.yith-ppwc-button-container') );

		toUnblock.forEach( (f) => {
			f.removeClass('processing').unblock();
		})
	}

	logger(...args) {
		if ( 'sandbox' === this.environment ) {
			console.log('yith_ppwc_google_pay', args);
		}
	}

}