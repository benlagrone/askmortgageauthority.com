import {useEffect, useState} from '@wordpress/element';
import PPCCFields from './PPCCFields';

import useSDK from '../useSDK';

const PPCC= ( props ) =>{
  const [isLoaded, setIsLoaded] = useState(false);
  const [cardFieldInstance, setCardFieldInstance] = useState(false);
  const {components: {LoadingMask}, description, activePaymentMethod, emitResponse, eventRegistration} = props;
  const {onPaymentSetup} = eventRegistration;
  const {threedsecure}  = yith_ppwc_card_settings;
  const sdk = useSDK(props);
  const {loadSDK} = sdk;


  useEffect(() => {
    let cardFieldsIn;

    function start() {
      try {
        const {cardFields, orderID} = loadSDK();
        cardFieldsIn = cardFields;
        setCardFieldInstance(cardFieldsIn);

        setIsLoaded(true);
      } catch (error) {
        console.log(error);
      }
    }

    start();
    return () => {
      setIsLoaded(false);
      setCardFieldInstance(false);
      cardFieldsIn && typeof cardFieldsIn.onChange !== "undefined" && cardFieldsIn?.teardown();
    };
  }, [loadSDK]);

  useEffect(() => {
    if ('yith_paypal_payments_custom_card' !== activePaymentMethod) {
      return;
    }

    const unsubscribe = onPaymentSetup(async () => {

      if (!cardFieldInstance) {
        return {
          type: emitResponse.responseTypes.ERROR,
          messageContext: emitResponse.noticeContexts.PAYMENTS,
          message: yith_ppwc_frontend.errorMessage,
        };
      }
      try {
        const paymentMethodData = {};
        let error = false;
        await cardFieldInstance.submit()
        .then(() => {
          const orderIDInput = document.querySelector('#yith_paypal_cc_payments-order-id');
          paymentMethodData['yith_paypal_cc_payments-order-id'] = jQuery(orderIDInput).val();
        }).catch( (err) => {
          error = err;
        })

        if( error ){
         return {
           type: emitResponse.responseTypes.ERROR,
           messageContext: emitResponse.noticeContexts.PAYMENTS,
           message:  error.message,
         }
        }else{
          return {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {paymentMethodData},
          };
        }

      } catch (error) {

        return {
          type: emitResponse.responseTypes.ERROR,
          messageContext: emitResponse.noticeContexts.PAYMENTS,
          message:  error,
        };
      }

    });
    return unsubscribe;
  }, [
    onPaymentSetup,
    cardFieldInstance,
    emitResponse.responseTypes.SUCCESS,
    emitResponse.responseTypes.ERROR,
    emitResponse.noticeContexts.PAYMENTS,
  ]);

  return (
      <>
        <div>{description}</div>
        <LoadingMask isLoading={!isLoaded} showSpinner={true}>
          <PPCCFields {...props} isLoaded={setIsLoaded}/>
        </LoadingMask>
      </>
  );
}

export default PPCC;