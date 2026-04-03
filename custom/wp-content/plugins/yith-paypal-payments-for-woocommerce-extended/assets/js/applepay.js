/* Starting Listener */
window.addEventListener('load', () => {
	(async () => {
		maybeApplepayShowButton();
	})();
	
});

async function maybeApplepayShowButton() {

	/* not a valid context so avoid to proceed */
	if ( '' === yith_ppwc_apple_pay.context ) {
		return;
	}

	//check if current device is valid
	if (!window.ApplePaySession) { 
		throw new Error('This device does not support Apple Pay');
	}
	//check if we can make payments
	if (!ApplePaySession.canMakePayments()) {
		throw new Error('This device, although an Apple device, is not capable of making Apple Pay payments');
	}

	//check container is present and context not empty
	let check_container = document.getElementById('applepay-container');

	if ( '' === yith_ppwc_apple_pay.context || ! check_container ) { return; }

	/* Get Apple Pay Paypal configuration */
	const applepayCfg = await paypal.Applepay().config();
	const { isEligible } = applepayCfg;

	if ( ! isEligible ) {
		throw new Error("applepay is not eligible");
	}

	const apbtn = new ApplePayButton(applepayCfg);
	apbtn.init();
}

class ApplePayButton {
	constructor(applepayCfg) {
		this.config = applepayCfg;
		this.context = yith_ppwc_apple_pay.context;
		this.ajaxUrl = yith_ppwc_frontend.ajaxUrl;
		this.environment = yith_ppwc_apple_pay.environment;
		this.buttonColor = yith_ppwc_apple_pay.buttonColor;
		this.buttonType = yith_ppwc_apple_pay.buttonType;
		this.buttonLocale = yith_ppwc_apple_pay.buttonLocale;
		this.form = '';
		this.fundingSource = yith_ppwc_apple_pay.fundingSource;
		this.formRequestType = '';
		this.current_ap_session;
		this.transactionInfos;
		this.requireShipping = yith_ppwc_apple_pay.needShipping == '1' ? true : false;
		this.productInfo = yith_ppwc_apple_pay.product == undefined ? '' : yith_ppwc_apple_pay.product;
	}
	/* init Events Handlers */
	initEvents() {
		jQuery(document.body).on('updated_cart_totals updated_checkout update_shipping_method', function() {
			maybeApplepayShowButton();
		});
	
		// Handle single product page.
		jQuery( 'form.variations_form' ).on( 'show_variation', function( ev, variation, purchasable ) {
			let b = jQuery('.yith-ppwc-button-container').find('#applepay-container');
			if ( purchasable ) {
				b.show();
			} else {
				b.hide();
			}
		} );
		jQuery( 'form.variations_form' ).on( 'hide_variation', function( ev, variation, purchasable ) {
			let b = jQuery('.yith-ppwc-button-container').find('#applepay-container');
			b.hide();
		} );
	}

	/* init the button */
	init () {
		this.getTransactionInfos().then(
			(data) => {
				this.transactionInfos = data;
				this.logger('TransactionInfos:', this.transactionInfos)
				this.renderButton();
			}
		);
		this.initEvents();
	}

	/* Render button */
	renderButton() {
		const buttonLocale= 'browser' === this.buttonLocale ? '': this.buttonLocale;
		const button =  '<apple-pay-button id="applepay_button" buttonstyle="' + this.buttonColor + '" type="' + this.buttonType + '" locale="' + buttonLocale + '">';

		let el = document.getElementById('applepay-container');
		el.innerHTML = button;
		document.getElementById("applepay_button").addEventListener("click", () => { this.buttonClickHandler() });
	}

	/* get payment request */
	getPaymentRequest() {
        let baseRequest = {
            merchantCapabilities: this.config.merchantCapabilities,
            supportedNetworks: this.config.supportedNetworks,
            requiredShippingContactFields: ["postalAddress", "email", "phone"],
            requiredBillingContactFields: ["postalAddress"], // ApplePay does not implement billing email and phone fields.
        }

		if ( ! this.requireShipping ) {
			// Minimum data required for order creation.
			baseRequest.requiredShippingContactFields = ["email", "phone"];
        }

		return Object.assign(this.transactionInfos, baseRequest);
	}

	/* set Apple Pay Session */
	setApplePaySession( paymentRequest ) {
		this.logger('starting new session for request:', paymentRequest);
		this.current_ap_session = new ApplePaySession(4, paymentRequest);
		if (this.requireShipping) {
			this.current_ap_session.onshippingmethodselected =this.onShippingMethodSelected(this.current_ap_session);
		}
		this.current_ap_session.onshippingcontactselected = this.onShippingContactSelected(this.current_ap_session);
		this.current_ap_session.onvalidatemerchant = this.onValidateMerchant(this.current_ap_session);
		this.current_ap_session.onpaymentauthorized = this.onPaymentAuthorized(this.current_ap_session);
		this.current_ap_session.oncancel = this.onUIDismissed.bind(this);
		this.current_ap_session.begin()
		this.logger( 'current apple pay session:', this.current_ap_session);
	}
	/* On payment modal closed */
	async onUIDismissed() {
		var body = [];
		this.logger('maybe clean session')
		body.push({name:'request', value: 'maybe_clean_session'});
		this.blockFormRequest();
		const res = await fetch(yith_ppwc_frontend.ajaxUrl, {
			method: 'POST',
			headers: {
				'content-type': 'application/x-www-form-urlencoded'
			},
			credentials: 'same-origin',
			body: this.formatRequestBody( body, 'applepay' ),
		});

		this.unblockFormRequest();
	}

	/* button click event handler */
	async buttonClickHandler(event) {
		this.logger('buttonClickHandler');

		const paymentRequest = this.getPaymentRequest();
		this.logger('buttonClickHandler-paymentRequest', paymentRequest);
		this.setApplePaySession(paymentRequest);

		return;
	}

	onShippingMethodSelected(session) {		
		return (event) => { 
			this.logger('onShippingMethodSelected call', event)
            const data = this.getShippingMethodData(event);

            jQuery.ajax({
                url: this.ajaxUrl,
                method: 'POST',
                data: data,
                success: (applePayShippingMethodUpdate, textStatus, jqXHR) => {
                    let response = applePayShippingMethodUpdate.data;
					this.logger('ajax response', applePayShippingMethodUpdate)
                    if (applePayShippingMethodUpdate.result === 'failure') {
						console.warn( applePayShippingMethodUpdate.error );
						session.abort();
                    } else {
						this.logger('Shipping Methods', response.newShippingMethods)
						// Sort the response shipping methods, so that the selected shipping method is the first one.
						response.newShippingMethods = response.newShippingMethods.sort((a, b) => {
							if (a.label === event.shippingMethod.label) {
								return -1;
							}
							return 1;
						});
						session.completeShippingMethodSelection(response);
					}
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    this.log('onshippingmethodselected error', textStatus);
                    console.warn(textStatus, errorThrown);
                    session.abort();
                },
            });
		};
	}

    getShippingMethodData(event) {
		return {
				request: 'update_shipping_method_applepay',
                shipping_method: event.shippingMethod,
				simplified_contact: event.shippingContact,
				security: yith_ppwc_apple_pay.ajaxNonce,
            };
    }

	onShippingContactSelected(session) {
		return (event) => {
            this.logger('onShippingContactSelected call');
			const data = this.getShippingContactData(event);
			this.logger('ShippingContactData ajax request', data);

			this.validate_cart().then( response => {
				if ( response.validate === false ) {
					this.logger( 'cart not validated')
					session.abort();
					return;
				}
				jQuery.ajax({
					url: this.ajaxUrl,
					method: 'POST',
					data: data,
					success: (ShippingContactUpdate, textStatus, jqXHR) => {
						this.logger('ShippingContactUpdate',ShippingContactUpdate)
						let response = ShippingContactUpdate.data;	
						if (ShippingContactUpdate.success === false) {
							console.error(ShippingContactUpdate.data)
							session.abort();
							return;
						}
						session.completeShippingContactSelection(response);
					},
					error: (jqXHR, textStatus, errorThrown) => {
						this.logger('onshippingcontactselected error', textStatus);
						console.warn(textStatus, errorThrown);
						session.abort();
					},
				});
			});
        };
	}

	getShippingContactData(event) {
		return {
			request: 'update_shipping_contact_applepay',
			simplified_contact: event.shippingContact,
			need_shipping: this.requireShipping,
			security: yith_ppwc_apple_pay.ajaxNonce,
		};
    }

	/**
	 * Validate Cart
	 * 
	 * @returns Promise
	 */
	validate_cart() {
		return new Promise( async(resolve, reject) => {
			try {
				var body = [];

				if ( 'product' === this.context ) {
					if ( this.form ) {	
						body = this.form.serializeArray();
						body.push({name:'request', value: 'validate_product_cart'});
						body.push( {name: 'is-yith-ppwc-action', value: 'yes'} );
						let addToCart = this.form.find( 'button[name="add-to-cart"]' );
						if ( addToCart.length ) {
							body.push({name: 'add-to-cart', value: addToCart.val()});
						}
					} else {
						return resolve(
							{ validate: false }
						);
					}
				} else {
					body.push({name:'request', value: 'validate_product_cart'});
				}
				this.logger('validate_cart for ' + this.context, body);
				this.blockFormRequest();
				const res = await fetch(this.ajaxUrl, {
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
			} catch(err) {
				console.log('validate_cart error', err);
				reject();
			}

		});
	}
	/* Validate Merchant */
	onValidateMerchant(session) {
		this.logger('Validating Merchant');

		return (applePayValidateMerchantEvent) => {
			paypal.Applepay().validateMerchant({
				validationUrl: applePayValidateMerchantEvent.validationURL,
			  })
			  .then(validateResult => {
				this.logger( 'Merchant validated',validateResult.merchantSession);
				session.completeMerchantValidation(validateResult.merchantSession);
				jQuery.ajax({
					url: this.ajaxUrl,
					type: 'POST',
					data: {
						request: 'validate_merchant',
						validation: true,
						security: yith_ppwc_frontend.ajaxNonce,
					}
				})
			  })
			  .catch(validateError => {
				console.error('yith_ppwc_apple_pay_blocks validate error', validateError);
				jQuery.ajax({
					url: this.ajaxUrl,
					type: 'POST',
					data: {
						request: 'validate_merchant',
						validation: false,
						security: yith_ppwc_frontend.ajaxNonce,
					}
				})
				session.abort();
			  });
		}
	}
	/* On payment authorized */
	onPaymentAuthorized(session) {
		this.logger('onPaymentAuthorized');
		return async (event) => { 
			this.logger('onPaymentAuthorized call');

			if ( ! this.needShipping && this.context === 'product' ) {
				const validation = await this.validate_cart();
				if ( validation.validate === false ) {
					this.logger( 'cart not validated')
					session.abort();
					return;
				}
			}
			let response = await this.createOrder(this, event.payment);

			if ( response?.result == 'failure' ) {
				console.error(response.error)
				//location.reload();
				session.completePayment(session.STATUS_FAILURE);
				session.abort();
				return;
			}

			let id = response.id;

			this.logger('onpaymentauthorized order created', id, event.payment.token, event.payment.billingContact);

            try {
                const confirmOrderResponse = await paypal.Applepay().confirmOrder({
                    orderId: id,
                    token: event.payment.token,
                    billingContact: event.payment.billingContact,
                });

				this.logger('confirmOrderResponse', confirmOrderResponse);

				if (confirmOrderResponse && confirmOrderResponse.approveApplePayPayment) {
                    if (confirmOrderResponse.approveApplePayPayment.status === "APPROVED") {
						try{
							this.blockFormRequest()
							let approved = false;
							const response = await this.approveOrder(
								this,
								{
								orderID: id
							});

							this.logger('approveOrder response', response);
	
							//we receive a redirect to checkout if the process went ok
							if ( response.redirect ) {
								approved = true;
							}
		
							if (approved) {
								this.logger('onpaymentauthorized approveOrder OK');
								session.completePayment(session.STATUS_SUCCESS);
								window.location.href = response.redirect;
							} else {
								this.logger('onpaymentauthorized approveOrder FAIL');
								session.completePayment(session.STATUS_FAILURE);
								if ( response.result && 'failure' === response.result ) {
									if ( response.message ) {
										console.error('onpaymentauthorized', response.message)
									}
								}
								session.abort();
							}

						} catch(error) {
                            session.completePayment(session.STATUS_FAILURE);
                            session.abort();
							console.log(error)
						}
					} else {
                        console.error('Order Confirmation Error: status is not APPROVED');
                        session.completePayment(session.STATUS_FAILURE);
						session.abort();
                    }
				} else {
                    console.error('Invalid confirmOrderResponse');
                    session.completePayment(session.STATUS_FAILURE);
					session.abort();
                }
			} catch (error) {
                //console.error('Confirm Order', error);
                session.completePayment(session.STATUS_FAILURE);
                session.abort();
			}

			this.unblockFormRequest()
		}
	}

	async approveOrder(config, data, actions) {
		this.logger('approving order');
		if ( data && data.orderID ) {

			const response = await fetch( this.ajaxUrl, {
				method: 'POST',
				headers: {
					'content-type': 'application/x-www-form-urlencoded'
				},
				body: this.formatRequestBody( [
					{name: 'request', value: 'approve_order'},
					{name: 'orderID', value: data.orderID},
					{name: 'checkoutRequest', value: this.context},
					{name: 'fundingSource', value: config.fundingSource}
				], 'applepay' ),
			} );
			return response.json();

		}
	}

	/* get the transaction informations */
	getTransactionInfos() {
		this.logger('getTransactionInfos');
		return new Promise( async ( resolve, reject ) => {
			try{
				var body = [];		

				if ( 'product' === this.context ) {
					let b = document.getElementById('applepay-container');
					this.form = jQuery(b).closest( '.product.type-product' ).find( 'form.cart' );
					let qty = this.form.find('.qty').val();
	
					if ( this.form.length ) {
						body.push({name:'request', value: 'product_cart_info'});
						body.push({name:'product_id', value: this.productInfo?.product_id});
						body.push({name:'product_qty', value: qty})
					}
				} else {
					body.push({name:'request', value: 'cart_info'});
				}
					
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
						this.logger( 'getTransactionInfos error', result.data );
                        return resolve(false);
                    }

					return resolve(
						{
							countryCode: result.data.countryCode,
							currencyCode: result.data.currencyCode,
							total: {
								label: result.data.total.label,
								type: result.data.total.type,
								amount: result.data.total.amount,
							}
						}
					)
				});
			}
			catch (err) {
				console.error('getTransactionInfos error',err);
				reject(err);
			}
		});
	}

	/**
	 * Create Order
	 */
	async createOrder(config, data, action) {
		return fetch( this.ajaxUrl, {
			method: 'POST',
			headers: {
				'content-type': 'application/x-www-form-urlencoded'
			},
			body: this.formatRequestBody( [
				{name: 'request', value: 'create_order'},
				{name: 'checkoutRequest', value: this.context},
				{name: 'orderID', value: yith_ppwc_apple_pay.orderId },
				{name: 'fundingSource', value: config.fundingSource},
				{name: 'billingData', value: JSON.stringify(data.billingContact)},
				{name: 'shippingData', value: JSON.stringify(data.shippingContact)},

			], 'applepay' )
		} )
			.then( function( res ) {
				return res.json();
			} )
			.then( function( data ) {
				return data; //when not error, this contains the id of the order
			} );
	}

	/* Format the request body for the fetch calls */
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

	/* logger function */
	logger(...args) {
		if ( 'sandbox' === this.environment ) {
			console.log('yith_ppwc_apple_pay', args);
		}
	}
}