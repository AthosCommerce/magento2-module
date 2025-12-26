define([
    'consoleLogger',
    'itemsTracking',
    'Magento_Customer/js/customer-data',
    'intelliSuggest',
], function (consoleLogger, itemsTracking, customerData) {
    'use strict';

    return function (config) {
        if (!config) return;

        var siteId = config.siteId;
        var productArr = config.products;
        var skuArr = config.sku;
        var trackingData = customerData.get('athoscommerce-tracking')();
        var shopperId = trackingData.shopper_id;

        try {
            IntelliSuggest.init({
                siteId: siteId,
                context: 'Basket',
                seed: skuArr
            });
            if (shopperId) {
                IntelliSuggest.setShopperId(shopperId);
            }
            itemsTracking(productArr)
            IntelliSuggest.inBasket({});
        } catch (err) {
            consoleLogger.error(err)
        }
    };
});
