/**
 * External dependencies
 */
import {useEffect, useState} from '@wordpress/element';
import {decodeEntities} from '@wordpress/html-entities';
import {getPaymentMethodData} from '@woocommerce/settings';
import {loadCustomScript} from "@paypal/paypal-js";
import GooglePayButton from '../yith-paypal-payments-google-pay/components/Button';
import Content from './components/Content';

const {registerExpressPaymentMethod} = wc.wcBlocksRegistry;

const settings = getPaymentMethodData('yith_paypal_payments', {});
const {sdkUrl, cancelContent, isCart, isCheckout, isConfirmed, isGooglePaying} = yith_ppwc_google_pay_blocks;
const label = decodeEntities(settings.description);

const Label = (props) => {
  const {PaymentMethodLabel} = props.components;
  return <PaymentMethodLabel text={label}/>;
};

const GooglePayButtonWrapper = (props) => {
	const [googlePayLoaded, setGooglePayLoaded] = useState(false);

	useEffect( () => {
		loadCustomScript( {url:sdkUrl}).then(() => {
			setGooglePayLoaded(true);
		});
	},[]);

	useEffect(()=>{
		if( googlePayLoaded ) {
			(async () => {
				const config = await paypal.Googlepay().config();
				const btn = new GooglePayButton(config, props);
				btn.init();
			})();

		}
	},[googlePayLoaded]);
	return (
		<div id="googlepay-container"></div>
	)
 }

if (isCart || (isCheckout && !isConfirmed && !isGooglePaying) || ( isCheckout && isConfirmed && isGooglePaying ) ) {
  const content = isConfirmed ? <Content content={cancelContent} /> : <GooglePayButtonWrapper />;

  const args = {
    name: 'yith_ppwc_google_pay',
    label: <Label/>,
    content,
    edit: <Content content={settings.description}/>,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
      features: settings?.supports ?? [],
    },
  };
  
  registerExpressPaymentMethod(args);
}