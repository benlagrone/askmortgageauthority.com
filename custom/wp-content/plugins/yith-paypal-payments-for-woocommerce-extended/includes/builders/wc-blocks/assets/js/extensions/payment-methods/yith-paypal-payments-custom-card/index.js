import {decodeEntities} from '@wordpress/html-entities';
import {getPaymentMethodData} from '@woocommerce/settings';

import {PAYMENT_METHOD_NAME} from './constants';
import PPCC from './components/PPCC';

const {registerPaymentMethod} = wc.wcBlocksRegistry;

const settings = getPaymentMethodData(PAYMENT_METHOD_NAME, {});
const label = decodeEntities(settings.title);
const description = decodeEntities(settings.description);

const Label = (props) => {
  const {PaymentMethodLabel} = props.components;
  return <><PaymentMethodLabel text={label}/><span style={{marginLeft: '5px'}}></span></>;
};

const PPCCWrapper = (props) => {
  const {isEditing} = props;
  if (isEditing) {
    return null;
  }

  return (
      <PPCC description={description} {...props} />
  );
};

const args = {
  name: PAYMENT_METHOD_NAME,
  label: <Label/>,
  content: <PPCCWrapper/>,
  edit: <PPCCWrapper/>,
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings?.supports ?? [],
  },
};

registerPaymentMethod(args);