import {PAYMENT_METHOD_NAME} from '../constants';

const PPCCFields = ({isLoaded, components: {LoadingMask}}) => {
  const {cardNumberLabel, expirationDateLabel, cvvLabel} = yith_ppwc_card_settings;
  return <LoadingMask isLoading={!isLoaded} showSpinner={true}>
    <div className={`wc-block-card-elements payment_method_${PAYMENT_METHOD_NAME}`}>

      <div className="wc-block-gateway-container wc-card-number-element">
        <div
            id="yith-ppwc-cc-card-number-block"
            className={`wc-block-yith-ppwc-field empty hosted-field`}
        />
        <label className="hosted-fields--label" htmlFor="yith-ppwc-cc-card-number-block">{cardNumberLabel}</label>
        <div id="card-image"></div>
      </div>
      <div className="wc-block-gateway-container wc-expiration-date-element">
        <div
            id="yith-ppwc-cc-expiration-date-block"
            className={`wc-block-yith-ppwc-field empty hosted-field`}
        />
        <label className="hosted-fields--label" htmlFor="yith-ppwc-cc-expiration-date-block">{expirationDateLabel}</label>
      </div>


      <div className="wc-block-gateway-container wc-expiration-date-element">
        <div
            id="yith-ppwc-cc-cvv-block"
            className={`wc-block-yith-ppwc-field empty hosted-field`}
        />
        <label className="hosted-fields--label" htmlFor="yith-ppwc-cc-cvv-block">{cvvLabel}</label>
      </div>
      <input type='hidden' value="" id="yith_paypal_cc_payments-order-id"/>
    </div>
  </LoadingMask>;
};

export default PPCCFields;