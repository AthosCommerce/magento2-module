define([
    'Magento_Customer/js/customer-data',
], function (customerData) {
    'use strict';

    var cartObservable = customerData.get('athos_pagemeta_cart');
    var magentoCartObservable = customerData.get('cart');
    var previousSnapshot = null;
    var isInitialized = false;

    function clone(value) {
        try {
            return JSON.parse(JSON.stringify(value || {}));
        } catch (e) {
            return value || {};
        }
    }

    function toNumber(value, fallback) {
        var parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function getProducts(snapshot) {
        return snapshot && Array.isArray(snapshot.products) ? snapshot.products : [];
    }

    function normalizeItem(item) {
        return {
            key: String(item.key || ''),
            uid: String(item.uid || ''),
            parentId: item.parentId === null || item.parentId === undefined ? null : String(item.parentId),
            sku: String(item.sku || ''),
            qty: toNumber(item.qty, 0),
            price: toNumber(item.price, 0)
        };
    }

    function normalizeProducts(snapshot) {
        return getProducts(snapshot).map(normalizeItem);
    }

    function aggregateItems(items) {
        var aggregated = {};

        items.forEach(function (item) {
            var key;

            if (!item || !item.key) {
                return;
            }

            key = item.key;

            if (!aggregated[key]) {
                aggregated[key] = {
                    key: item.key,
                    uid: item.uid,
                    parentId: item.parentId,
                    sku: item.sku,
                    qty: 0,
                    price: item.price
                };
            }

            aggregated[key].qty += toNumber(item.qty, 0);

            if (!aggregated[key].price && item.price) {
                aggregated[key].price = item.price;
            }
        });

        return aggregated;
    }

    function aggregatedItemsToArray(index) {
        return Object.keys(index).map(function (key) {
            return index[key];
        });
    }

    function reduceForBeacon(items) {
        return items.map(function (item) {
            return {
                uid: item.uid,
                parentId: item.parentId,
                sku: item.sku,
                qty: item.qty,
                price: item.price
            };
        });
    }

    function buildPayload(results, cartItems) {
        return {
            data: {
                results: reduceForBeacon(results),
                cart: reduceForBeacon(cartItems)
            }
        };
    }

    function dispatchEvent(action, payload) {
        window.dispatchEvent(new CustomEvent('athos:magento:cart:' + action, {
            detail: payload
        }));

        if (
            window.athos &&
            window.athos.tracker &&
            window.athos.tracker.events &&
            window.athos.tracker.events.cart &&
            typeof window.athos.tracker.events.cart[action] === 'function'
        ) {
            window.athos.tracker.events.cart[action](payload);
        }
    }

    function areItemsEqual(previousSnapshotData, currentSnapshotData) {
        var previousAggregated = aggregateItems(normalizeProducts(previousSnapshotData));
        var currentAggregated = aggregateItems(normalizeProducts(currentSnapshotData));

        return JSON.stringify(previousAggregated) === JSON.stringify(currentAggregated);
    }

    function hasStoreChanged() {
        var cart;

        if (!window.checkout || typeof magentoCartObservable !== 'function') {
            return false;
        }

        cart = magentoCartObservable();

        return (
            cart.website_id !== undefined &&
            window.checkout.websiteId !== undefined &&
            String(cart.website_id) !== String(window.checkout.websiteId)
        ) || (
            cart.storeId !== undefined &&
            window.checkout.storeId !== undefined &&
            String(cart.storeId) !== String(window.checkout.storeId)
        );
    }

    function diffSnapshots(previousSnapshotData, currentSnapshotData) {
        var previousIndex = aggregateItems(normalizeProducts(previousSnapshotData));
        var currentIndex = aggregateItems(normalizeProducts(currentSnapshotData));
        var currentCartItems = aggregatedItemsToArray(currentIndex);
        var addedResults = [];
        var removedResults = [];

        Object.keys(currentIndex).forEach(function (key) {
            var currentItem = currentIndex[key];
            var previousItem = previousIndex[key];

            if (!previousItem) {
                addedResults.push(currentItem);
                return;
            }

            if (currentItem.qty > previousItem.qty) {
                addedResults.push({
                    key: currentItem.key,
                    uid: currentItem.uid,
                    parentId: currentItem.parentId,
                    sku: currentItem.sku,
                    qty: currentItem.qty - previousItem.qty,
                    price: currentItem.price
                });
            }
        });

        Object.keys(previousIndex).forEach(function (key) {
            var previousItem = previousIndex[key];
            var currentItem = currentIndex[key];

            if (!currentItem) {
                removedResults.push(previousItem);
                return;
            }

            if (currentItem.qty < previousItem.qty) {
                removedResults.push({
                    key: previousItem.key,
                    uid: previousItem.uid,
                    parentId: previousItem.parentId,
                    sku: previousItem.sku,
                    qty: previousItem.qty - currentItem.qty,
                    price: previousItem.price
                });
            }
        });

        if (addedResults.length > 0) {
            dispatchEvent('add', buildPayload(addedResults, currentCartItems));
        }

        if (removedResults.length > 0) {
            dispatchEvent('remove', buildPayload(removedResults, currentCartItems));
        }
    }

    function startTracking() {
        var currentSnapshot;

        if (isInitialized) {
            return;
        }

        isInitialized = true;
        previousSnapshot = clone(cartObservable());

        cartObservable.subscribe(function (updatedSnapshot) {
            currentSnapshot = clone(updatedSnapshot);

            if (areItemsEqual(previousSnapshot, currentSnapshot)) {
                previousSnapshot = currentSnapshot;
                return;
            }

            diffSnapshots(previousSnapshot, currentSnapshot);
            previousSnapshot = currentSnapshot;
        });

        if (hasStoreChanged()) {
            customerData.reload(['cart', 'athos_pagemeta_cart'], false);
        }
    }

    return function () {
        startTracking();
    };
});
