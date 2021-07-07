define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'boipa',
                component: 'BOIPA_Payment/js/view/payment/method-renderer/boipastandard'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
