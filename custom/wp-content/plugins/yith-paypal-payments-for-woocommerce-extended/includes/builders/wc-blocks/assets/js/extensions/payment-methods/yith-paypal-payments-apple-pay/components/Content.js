import parse from 'html-react-parser';

export default function Content({isPending, pendingContent, content}) {
	if ( yith_ppwc_apple_pay_blocks.isApplePaying ) {
		jQuery('fieldset#payment-method').css('display', 'none');
		jQuery('.wc-block-components-express-payment-continue-rule--checkout').css('display','none');
	}
	
  return parse( isPending ? pendingContent : content );
}