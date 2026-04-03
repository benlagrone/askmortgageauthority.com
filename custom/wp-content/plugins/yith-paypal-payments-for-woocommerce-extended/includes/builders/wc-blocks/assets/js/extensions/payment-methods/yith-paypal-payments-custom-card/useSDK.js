import {useState, useCallback, useMemo} from '@wordpress/element';
import {formatRequestBody} from '../base/common';

const useSDK = ({billing, shippingData, eventRegistration}) => {
  const {ajaxUrl, liabilityList, threedsecure, ajaxNonce} = yith_ppwc_card_settings;
  const [cardFieldsInstance, setCardFieldsInstance] = useState('');
  const {onSubmit} = eventRegistration;

  const loadSDK = useCallback(
      () => {
        let orderID = '';
        const cardFields = paypal.CardFields({
          style: {
            'input': {
              'font-size': '15px',
              'font-weight': 'lighter',
              'color': '#ccc',
              'padding':'2.5em 1em 1em'
            },
            ':focus': {
              'color': 'black',
            },
            '.valid': {
              'color': 'black',
            },
            '.invalid': {
              'color': 'black',
            },
          },
          createOrder: (data, actions) => {
            return fetch(ajaxUrl, {
              method: 'POST',
              headers: {
                'content-type': 'application/x-www-form-urlencoded',
              },
              body: formatRequestBody([
                {name: 'request', value: 'create_order'},
                {name: 'checkoutRequest', value: 'checkout'},
                {name: 'orderID', value: ''},
              ], ajaxNonce),
            }).then(function(res) {
              return res.json();
            }).then(function(data) {

              if( data.error ){
                console.log('undefined', data.error);
                throw data.error;
              }
              const orderIDInput = document.querySelector('#yith_paypal_cc_payments-order-id');
              orderIDInput.value = data.id;
              return data.id; // Use the same key name for order ID on the client and server
            });
          },
          onApprove: (data, actions) => {

            if (data && data.orderID) {
              if (threedsecure) {
                let {liabilityShift} = data;

                if ( typeof liabilityShift !== 'undefined') {
                  liabilityShift = liabilityShift.toLowerCase();
                  if (liabilityList.indexOf(liabilityShift) === -1) {
                    if (liabilityShift === 'unknown') {
                      throw yith_ppwc_frontend.secure_3d_unknown;
                    } else if (liabilityShift === 'no') {
                      throw yith_ppwc_frontend.secure_3d_no;
                    }
                  }
                }
              }

              fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                  'content-type': 'application/x-www-form-urlencoded',
                },
                body: formatRequestBody([
                  {name: 'request', value: 'approve_order'},
                  {name: 'orderID', value: data.orderID},
                  {name: 'checkoutRequest', value: 'checkout'},
                ]),
              }).then(function(res) {
                return res.json();
              }).then(function(json) {
                if (json) {
                  if (json.redirect) {
                    window.location.href = json.redirect;
                  } else if (json.result && 'failure' === json.result) {
                    window.location.reload();
                  }
                }
                typeof onSubmit !== 'undefined' && onSubmit();
              }).catch((err) => {
                throw err;
              });
            }
          },
          /*onError: function(error) {
            // Do something with the error from the SDK
            console.log('error');
            console.log(error);
            throw error;
          },*/
        });

        // Render each field after checking for eligibility
        if (cardFields.isEligible()) {

          const numberField = cardFields.NumberField();
          numberField.render('#yith-ppwc-cc-card-number-block');

          const cvvField = cardFields.CVVField();
          cvvField.render('#yith-ppwc-cc-cvv-block');

          const expiryField = cardFields.ExpiryField();
          expiryField.render('#yith-ppwc-cc-expiration-date-block');

        }

        setCardFieldsInstance(cardFields);

        return {
          cardFields,
        };
      }, []);

  return {
    cardFieldsInstance,
    loadSDK,
  };
};

export default useSDK;