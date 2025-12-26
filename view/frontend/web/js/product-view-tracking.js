define([
    'consoleLogger',
    'Magento_Customer/js/customer-data',
    'intelliSuggest'
], function (consoleLogger, customerData) {
    'use strict';

    return function (config) {
        if (!config) return;

        var sku = config.sku;
        var siteId = config.siteId;
        var trackingData = customerData.get('athoscommerce-tracking')();
        var shopperId = trackingData.shopper_id;
        try {
            IntelliSuggest.init({
                siteId: siteId,
                context: 'Product/' + sku,
                seed: [
                    sku
                ]
            });
            if (shopperId) {
                IntelliSuggest.setShopperId(shopperId);
            }
            IntelliSuggest.viewItem({
                sku: sku
            });
        } catch (err) {
            consoleLogger.error(err)
        }
    };
});
