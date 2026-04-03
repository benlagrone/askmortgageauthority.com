class GooglePayButton {

	constructor(googlepayCfg, props) {
		this.config = googlepayCfg;
		this.paymentsClient = null;
		this.context = yith_ppwc_google_pay_blocks.context;
		this.shippingCountries = yith_ppwc_google_pay_blocks.countries;
		this.environment = yith_ppwc_google_pay_blocks.environment;
		this.buttonColor = yith_ppwc_google_pay_blocks.buttonColor;
		this.buttonType = yith_ppwc_google_pay_blocks.buttonType;
		this.buttonSizeMode = yith_ppwc_google_pay_blocks.buttonSizeMode;
		this.buttonLocale = yith_ppwc_google_pay_blocks.buttonLocale;
		this.ajaxUrl = yith_ppwc_google_pay_blocks.ajaxUrl;
		this.ajaxNonce = yith_ppwc_google_pay_blocks.ajaxNonce;
		this.props = props ? props : '';
		this.fundingSource = yith_ppwc_google_pay_blocks.fundingSource;
		this.formRequestType = '';
		this.baseRequest = {
			apiVersion: 2,
			apiVersionMinor: 0
		}; // @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|apiVersion in PaymentDataRequest}
	}
	/* init the button */
	init () {

		let check_container = document.getElementById('googlepay-container');

		if ( '' === this.context || ! check_container ) { return; }
	
		/* Get Google Pay Paypal configuration */
		const googlepayCfg = this.config;

		const { isEligible } = googlepayCfg;
	
		if ( ! isEligible ) {
			throw new Error("googlepay is not eligible");
		}

		this.paymentsClient = this.getGooglePaymentsClient();
	
		this.logger('allowedPaymentMethods', this.config.allowedPaymentMethods);
		this.paymentsClient.isReadyToPay(this.getGoogleIsReadyToPayRequest(this.config.allowedPaymentMethods, this.config))
			.then((response) => {
				if (response.result) {
					this.renderButton();
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

		/* we do not check about Product page contest as it is not have block at the moment */

		/* force our paypal payments to be checked */
		wp.data.dispatch('wc/store/payment').__internalSetActivePaymentMethod('yith_paypal_payments');
		
		const paymentDataRequest = await this.getGooglePaymentDataRequest();
		this.paymentsClient.loadPaymentData(paymentDataRequest).then().catch((err) => {
			if ( err?.statusCode === 'CANCELED' ) {
				/* this will remove the wc blocks mask class and make the google pay button clickable again */
				wp.data.dispatch('wc/store/payment').__internalSetActivePaymentMethod('');
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
			this.logger('onPaymentDataChanged:transactionInfo', transactionInfo);

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

				this.logger('getGoogleTransactionInfo fetch')
				
				body.push({name:'request', value: 'cart_info'});

				await fetch(this.ajaxUrl, {
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
		return fetch( this.ajaxUrl, {
			method: 'POST',
			headers: {
				'content-type': 'application/x-www-form-urlencoded'
			},
			body: this.formatRequestBody( [
				{name: 'request', value: 'create_order'},
				{name: 'checkoutRequest', value: this.context},
				{name: 'orderID', value: yith_ppwc_google_pay_blocks.orderId },
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

			fetch( this.ajaxUrl, {
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

						// coming from cart or product so we go to checkout to continue.
						if ( json.redirect ) {
							window.location.href = json.redirect;
							return; //this is to avoid the submit after that only should be done in checkout.
						}
						
						config.props.onSubmit();
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
			{name: 'security', value: this.ajaxNonce},
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
	blockFormRequest() {
		let toblock = [];
		switch ( this.context ) {
			case 'cart':
				toblock.push( jQuery('.wp-block-woocommerce-filled-cart-block') );
				break;
			case 'checkout':
				toblock.push( jQuery('.wc-block-checkout') );
		}

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
				toUnblock.push( jQuery('.wp-block-woocommerce-filled-cart-block') );
				break;
			case 'checkout':
				toUnblock.push( jQuery('.wc-block-checkout') );
		}

		toUnblock.forEach( (f) => {
			f.removeClass('processing').unblock();
		})
	}
	logger(...args) {
		if ( 'sandbox' === this.environment ) {
			console.log('yith_ppwc_google_pay_blocks', args);
		}
	}

}

export default GooglePayButton;