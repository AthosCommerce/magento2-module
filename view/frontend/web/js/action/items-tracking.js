define([
    'underscore',
    'intelliSuggest'
], function (_) {
    return function (items) {
        _.each(items, function (item) {
            IntelliSuggest.haveItem({
                sku: item.sku,
                qty: item.qty,
                price: item.price
            });
        });
    };
})
