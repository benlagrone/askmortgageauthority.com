/**
 * External dependencies
 */
import {useEffect, useState} from '@wordpress/element';
import {decodeEntities} from '@wordpress/html-entities';
import {getPaymentMethodData} from '@woocommerce/settings';
import {loadCustomScript} from "@paypal/paypal-js";
import ApplePayButton from '../yith-paypal-payments-apple-pay/components/Button';
import Content from './components/Content';

const {registerExpressPaymentMethod} = wc.wcBlocksRegistry;

const settings = getPaymentMethodData('yith_paypal_payments', {});
const {sdkUrl, cancelContent, isCart, isCheckout, isConfirmed, isApplePaying} = yith_ppwc_apple_pay_blocks;
const label = decodeEntities(settings.description);

const Label = (props) => {
  const {PaymentMethodLabel} = props.components;
  return <PaymentMethodLabel text={label}/>;
};

const ApplePayButtonWrapper = (props) => {
	const [applePayLoaded, setApplePayLoaded] = useState(false);

	useEffect( () => {
		loadCustomScript( {url:sdkUrl}).then(() => {
			setApplePayLoaded(true);
		});
	},[]);

	useEffect(()=>{
		if( applePayLoaded ) {
			(async () => {
				const config = await paypal.Applepay().config();
				const btn = new ApplePayButton(config, props);
				btn.init();
			})();

		}
	},[applePayLoaded]);
	return (
		<div id="applepay-container"></div>
	)
 }

if (isCart || (isCheckout && !isConfirmed && !isApplePaying) || ( isCheckout && isConfirmed && isApplePaying ) ) {
  const content = isConfirmed ? <Content content={cancelContent} /> : <ApplePayButtonWrapper />;

  const args = {
    name: 'yith_ppwc_apple_pay',
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