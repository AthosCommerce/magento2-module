define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    var cartObservable = customerData.get('cart');
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

    function normalizeString(value) {
        if (value === undefined || value === null) {
            return '';
        }

        return String(value);
    }

    function normalizeNullableString(value) {
        if (value === undefined || value === null || value === '') {
            return null;
        }

        return String(value);
    }

    function normalizeItem(item) {
        return {
            uid: normalizeString(
                item.product_id ||
                item.item_id ||
                item.id ||
                item.uid
            ),
            parentId: normalizeNullableString(
                item.parent_id ||
                item.parentId ||
                item.product_id ||
                item.item_id ||
                item.id ||
                item.uid
            ),
            sku: normalizeString(
                item.product_sku ||
                item.sku ||
                ''
            ),
            qty: toNumber(
                item.qty ||
                item.qty_value ||
                0,
                0
            ),
            price: toNumber(
                item.product_price_value ||
                item.price_amount ||
                item.price ||
                0,
                0
            )
        };
    }

    function getSnapshotItems(snapshot) {
        if (!snapshot || !Array.isArray(snapshot.items)) {
            return [];
        }

        return snapshot.items.map(normalizeItem);
    }

    function getItemKey(item) {
        return normalizeString(item.uid) + '::' + normalizeString(item.parentId || '');
    }

    function indexItems(items) {
        var indexed = {};

        items.forEach(function (item) {
            indexed[getItemKey(item)] = item;
        });

        return indexed;
    }

    function buildPayload(results, cartItems) {
        return {
            data: {
                results: results,
                cart: cartItems
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

    function diffCartSnapshots(previousSnapshotData, currentSnapshotData) {
        var previousItems = getSnapshotItems(previousSnapshotData);
        var currentItems = getSnapshotItems(currentSnapshotData);

        var previousIndex = indexItems(previousItems);
        var currentIndex = indexItems(currentItems);

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
                    uid: previousItem.uid,
                    parentId: previousItem.parentId,
                    sku: previousItem.sku,
                    qty: previousItem.qty - currentItem.qty,
                    price: previousItem.price
                });
            }
        });

        if (addedResults.length > 0) {
            dispatchEvent('add', buildPayload(addedResults, currentItems));
        }

        if (removedResults.length > 0) {
            dispatchEvent('remove', buildPayload(removedResults, currentItems));
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

            if (previousSnapshot) {
                diffCartSnapshots(previousSnapshot, currentSnapshot);
            }

            previousSnapshot = currentSnapshot;
        });
    }

    return function () {
        startTracking();
    };
});
