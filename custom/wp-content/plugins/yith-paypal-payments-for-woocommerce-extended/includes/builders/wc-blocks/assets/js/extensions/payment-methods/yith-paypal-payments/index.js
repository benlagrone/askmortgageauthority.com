/**
 * External dependencies
 */
/** global yith_ppwc_settings **/
import {useEffect, useState} from '@wordpress/element';
import {decodeEntities} from '@wordpress/html-entities';
import {getPaymentMethodData} from '@woocommerce/settings';
import {
  PayPalScriptProvider,
  PayPalButtons,
  usePayPalScriptReducer, FUNDING,
} from '@paypal/react-paypal-js';

import {formatRequestBody} from '../base/common';
import Content from './components/Content';
import {PAYMENT_METHOD_NAME} from './constants';

const {registerPaymentMethod, registerExpressPaymentMethod} = wc.wcBlocksRegistry;

const settings = getPaymentMethodData('yith_paypal_payments', {});
const {isCart, isCheckout, isConfirmed, ajaxUrl, ajaxNonce, fundingSource} = yith_ppwc_settings;
const label = decodeEntities(settings.title);

const Label = (props) => {
  const {PaymentMethodLabel} = props.components;
  return <PaymentMethodLabel text={label}/>;
};

// This value is from the props in the UI
const style = {'layout': 'vertical'};

const PayPalButtonWrapper = (props) => {
  const [clientToken, setClientToken] = useState(null);
  const {options} = yith_ppwc_settings;

  useEffect(() => {
    fetch(ajaxUrl, {
      method: 'POST',
      headers: {
        'content-type': 'application/x-www-form-urlencoded',
      },
      body: formatRequestBody([
        {name: 'request', value: 'client_token'},
      ]),
    }).then(function(res) {
      return res.json();
    }).then(function(data) {
      return setClientToken(data.token.client_token); // Use the same key name for order ID on the client and server
    });
  }, []);

  return (
      <div style={{maxWidth: '750px'}}>
        <PayPalScriptProvider options={{...options, dataClientToken: clientToken}}
                              deferLoading={true}>
          <ButtonWrapper {...props}/>
        </PayPalScriptProvider>
      </div>
  );
};

const ButtonWrapper = (props) => {
  const {activePaymentMethod, eventRegistration, emitResponse, onSubmit} = props;
  const {onPaymentSetup} = eventRegistration;

  const [{isPending}, dispatch] = usePayPalScriptReducer();
  const [order, setOrder] = useState('');

  // load sdk
  useEffect(() => {
    dispatch({
      type: 'setLoadingStatus',
      value: 'pending',
    });
  }, []);
  /*
    useEffect(() => {
      if (PAYMENT_METHOD_NAME !== activePaymentMethod) {
        return;
      }

      const unsubscribe = onPaymentSetup(async () => {

        try {
          wp.data.select('wc/store/validation').hasValidationErrors()
          if(   wp.data.select('wc/store/validation').hasValidationErrors() ){
            return {
              type: emitResponse.responseTypes.ERROR,
              messageContext: emitResponse.noticeContexts.PAYMENTS,
              message: yith_ppwc_frontend.errorMessage,
            };
          }

        } catch (error) {

          return {
            type: emitResponse.responseTypes.ERROR,
            messageContext: emitResponse.noticeContexts.PAYMENTS,
            message: yith_ppwc_frontend.errorMessage,
          };
        }

      });
      return unsubscribe;
    }, [
      onPaymentSetup,
      emitResponse.responseTypes.SUCCESS,
      emitResponse.responseTypes.ERROR,
      emitResponse.noticeContexts.PAYMENTS,
    ]);*/

  const createOrder = (data) => {
    return fetch(ajaxUrl, {
      method: 'POST',
      headers: {
        'content-type': 'application/x-www-form-urlencoded',
      },
      body: formatRequestBody([
        {name: 'request', value: 'create_order'},
        {name: 'checkoutRequest', value: 'checkout'},
        {name: 'orderID', value: order},
      ], ajaxNonce),
    }).then(function(res) {
      return res.json();
    }).then(function(data) {
      setOrder(data.id);
      return data.id; // Use the same key name for order ID on the client and server
    });
  };

  const onClick = (data, actions) => {
    if (wp.data.select('wc/store/validation').hasValidationErrors()) {
      onSubmit();
      return false;
    }
  };

  const onApprove = (data, actions) => {
    if (data && data.orderID) {
      setOrder(data.orderID);
      fetch(ajaxUrl, {
        method: 'POST',
        headers: {
          'content-type': 'application/x-www-form-urlencoded',
        },
        body: formatRequestBody([
          {name: 'request', value: 'approve_order'},
          {name: 'orderID', value: data.orderID},
          {name: 'checkoutRequest', value: isCart ? 'cart' : 'checkout'},
        ]),
      }).then(function(res) {
        return res.json();
      }).then(function(json) {
        if (json) {
          if (json.redirect) {
            window.location.href = json.redirect;
            return;
          } else if (json.result && 'failure' === json.result) {
            window.location.reload();
          }
        }

        onSubmit();
      });
    }
  };
  const hasCard = fundingSource?.find(source => source = 'card');

  return (
      <>
        {!isPending &&
            <>
              <PayPalButtons
                  style={style}
                  disabled={false}
                  forceReRender={[style]}
                  fundingSource={FUNDING.PAYPAL}
                  createOrder={createOrder}
                  onApprove={onApprove}
                  onClick={onClick}
                  key={'paypal'}
              />
              {fundingSource && fundingSource.map((source, index) => {
                source !== 'card' && <PayPalButtons key={`${source}-${index}`}
                                                    style={style}
                                                    disabled={false}
                                                    forceReRender={[style]}
                                                    fundingSource={source}
                                                    createOrder={createOrder}
                                                    onApprove={onApprove}
                                                    onClick={onClick}
                />}
              )
              }

              {hasCard && <PayPalButtons key={'card'} style={style}
                                         disabled={false}
                                         forceReRender={[style]}
                                         fundingSource={'card'}
                                         createOrder={createOrder}
                                         onApprove={onApprove}
                                         onClick={onClick}/>}

            </>
        }
      </>
  );
};

if (isCart || isCheckout) {
  let registerMethod = isCart ? registerExpressPaymentMethod : registerPaymentMethod;
  const content = isConfirmed ? <Content content={yith_ppwc_settings.cancelContent}/> : <PayPalButtonWrapper/>;

  const args = {
    name: PAYMENT_METHOD_NAME,
    label: <Label/>,
    content,
    edit: <Content content={settings.description}/>,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
      features: settings?.supports ?? [],
    },
  };

  registerMethod(args);
}