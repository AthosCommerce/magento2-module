# AthosCommerce Feed module for Magento 2
This module generates product data feed and syncs it with AthosCommerce platform.
This module also automatically install the Athoscommerce tracking scripts on Product Detail Page, Cart Page, Checkout Success Page.

### Installation & Setup
```
composer require athoscommerce/magento2-module
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

### Verify that the extension is enabled
``
bin/magento module:status AthosCommerce_Feed
``

### Upgrade
```
composer update athoscommerce/magento2-module
bin/magento setup:upgrade --keep-generated
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:clean
```

---------

## Module Extensibility

###  Product Feed: Product Collection modification

- 

###  Product Feed: Product Data modification

- 

###  Storefront Tracking: To Add Custom Product Type 

In `app/code/AthosCommerce/Feed/etc/frontend/di.xml` you can add your custom product type, for example

```xml
<type name="AthosCommerce\Feed\Service\Tracking\CompositeOrderItemPriceResolver">
    <arguments>
        <argument name="orderItemPriceResolversPool" xsi:type="array">
            <item name="yourProductType" xsi:type="object">AthosCommerce\Feed\Service\Tracking\YourProductTypeItemPriceResolver</item>
        </argument>
    </arguments>
</type>
```

Then add to `app/code/AthosCommerce/Feed/Service/Tracking` directory your custom class, 
for example `YourProductTypeItemPriceResolver.php`:

```php
class YourProductTypeItemPriceResolver implements OrderItemPriceResolverInterface
{
    public function getProductPrice(OrderItemInterface $product): ?float
    {
        //your custom code
    }
}
```


Also you can do the same for `quoteItem`:

```xml
<type name="AthosCommerce\Feed\Service\Tracking\CompositeOrderItemPriceResolver">
    <arguments>
        <argument name="quoteItemPriceResolversPool" xsi:type="array">
            <item name="yourProductType" xsi:type="object">AthosCommerce\Feed\Service\Tracking\YourProductTypeItemPriceResolver</item>
        </argument>
    </arguments>
</type>
```

Then add to `app/code/AthosCommerce/Feed/Service/Tracking` directory your custom class, 
for example `YourProductTypeItemPriceResolver.php`:

```php
class YourProductTypeItemPriceResolver implements QuoteItemPriceResolverInterface
{
    public function getProductPrice(CartItemInterface $product): ?float
    {
        //your custom code
    }
}
```


Also, you can do the same for `quoteItem`:

```xml
<type name="AthosCommerce\Feed\Service\Tracking\CompositeSkuResolver">
    <arguments>
        <argument name="skuResolversPool" xsi:type="array">
            <item name="yourProductType" xsi:type="object">AthosCommerce\Feed\Service\Tracking\YourProductTypeSkuResolver</item>
        </argument>
    </arguments>
</type>
```

`YourProductTypeItemSkuResolver.php`:

```php
class YourProductTypeSkuResolver implements SkuResolverInterface
{ 
    public function getProductSku($product): ?string
    {
        //your custom code
    }
}
```
