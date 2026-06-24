define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    var cartObservable = customerData.get('athos_pagemeta_cart');
    var magentoCartObservable = customerData.get('cart');
    var isInitialized = false;
    var previousSnapshot = null;

    function getMagentoCartSnapshot() {
        return typeof magentoCartObservable === 'function' ? (magentoCartObservable() || {}) : {};
    }

    function isMagentoCartEmpty() {
        var cart = getMagentoCartSnapshot();
        var summaryCount = Number(cart.summary_count || 0);
        var items = Array.isArray(cart.items) ? cart.items : [];

        return summaryCount <= 0 || items.length === 0;
    }

    function hasAthosCartProducts(state) {
        return state.getProducts(cartObservable()).length > 0;
    }

    function startTracking() {
        var state = window.athosMagentoCartTrackerState;

        if (isInitialized) {
            return;
        }

        isInitialized = true;

        if (!state) {
            return;
        }

        if (isMagentoCartEmpty() && hasAthosCartProducts(state)) {
            previousSnapshot = null;
            customerData.reload(['cart', 'athos_pagemeta_cart'], false);
        } else {
            previousSnapshot = state.clone(cartObservable());
        }

        cartObservable.subscribe(function (updatedSnapshot) {
            var currentSnapshot = state.clone(updatedSnapshot);

            if (isMagentoCartEmpty()) {
                previousSnapshot = currentSnapshot;
                return;
            }

            if (previousSnapshot === null) {
                previousSnapshot = currentSnapshot;
                return;
            }

            if (state.areItemsEqual(previousSnapshot, currentSnapshot)) {
                previousSnapshot = currentSnapshot;
                return;
            }

            state.diffSnapshots(previousSnapshot, currentSnapshot);
            previousSnapshot = currentSnapshot;
        });

        if (state.hasStoreChanged(magentoCartObservable)) {
            previousSnapshot = null;
            customerData.reload(['cart', 'athos_pagemeta_cart'], false);
        }
    }

    return function () {
        startTracking();
    };
});
