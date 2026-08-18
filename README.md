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
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:clean
```

---------
## Compatible with:
- Adobe Commerce 2.4.4-p18, 2.4.5-p17, 2.4.6-p15, 2.4.7-p10, 2.4.8-p5, 2.4.9.
- Magento Open Source 2.4.4-p18, 2.4.5-p17, 2.4.6-p15, 2.4.7-p10, 2.4.8-p5, 2.4.9.
- PHP 8.1, 8.2, 8.3, 8.4, 8.5

This module may function on earlier releases, but we only officially support the latest patch version listed above.

## Change Log
Visit the <a href="https://github.com/athoscommerce/magento2-module/releases" target="_blank">Releases</a> page to see the latest changes and download previous versions.

---------
# Module Extensibility

##  Product Feed: Product Collection modification

### Overview

The feed product collection is built using a modifier pipeline. 
Each modifier is responsible for applying filters, joins, or attribute selections to the product collection.

### Creating a Custom Data Provider
#### Step 1: Create the Modifier Class

```php
namespace Vendor\Module\Model\Feed\Collection;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use AthosCommerce\Feed\Model\Feed\Collection\ModifierInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

class CustomVisibilityModifier implements ModifierInterface
{
    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     * @return Collection
     */
    public function modify(
        Collection $collection, 
        FeedSpecificationInterface $feedSpecification
    ): Collection
    {
        $collection->addAttributeToFilter('visibility', ['neq' => 1]);
        return $collection;
    }
}
```

#### Step 2: Register the Modifier via DI
In `app/code/Vendor/Module/etc/di.xml` add your custom modifier:

```xml
<type name="AthosCommerce\Feed\Model\Feed\CollectionProvider">
    <arguments>
        <argument name="modifiers" xsi:type="array">
            <item name="custom_visibility" xsi:type="array">
                <item name="objectInstance" xsi:type="object">Vendor\Module\Model\Feed\Collection\CustomVisibilityModifier</item>
                <item name="sortOrder" xsi:type="number">350</item>
            </item>
        </argument>
    </arguments>
</type>
```

##  Product Feed: Product Data Provider modification

### Overview

Product feed data is generated using a **Data Provider Pool**.
Each data provider contributes a specific part of the product payload (price, stock, attributes, options, etc.).

### Creating a Custom Data Provider 
#### Step 1: Create the Provider Class

```php
namespace Vendor\Module\Model\Feed\DataProvider;

use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use Magento\Catalog\Model\Product;

class CustomBadgeProvider implements DataProviderInterface
{
    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function getData(
        array $products, 
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        foreach ($products as &$product) {
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            $product['custom_badge'] = $productModel->getData('custom_badge');
        }

        return $products;
    }
    
    public function reset(): void
    {
        // reset any internal state if needed
    }

    public function resetAfterFetchItems(): void
    {
        // reset any internal state if needed
    }
}
```

### Step 2: Register the Provider via DI
In `app/code/Vendor/Module/etc/di.xml` add your custom data provider:

```xml
<type name="AthosCommerce\Feed\Model\Feed\DataProviderPool">
    <arguments>
        <argument name="dataProviders" xsi:type="array">
            <item name="custom_badge_provider" xsi:type="object">Vendor\Module\Model\Feed\DataProvider\CustomBadgeProvider</item>
        </argument>
    </arguments>
</type>
```

###  Storefront Tracking: To Add Custom Product Type 

In `app/code/Vendor/Module/etc/frontend/di.xml` you can add your custom product type, for example

```xml
<type name="AthosCommerce\Feed\Service\Tracking\CompositeOrderItemPriceResolver">
    <arguments>
        <argument name="orderItemPriceResolversPool" xsi:type="array">
            <item name="yourProductType" xsi:type="object">AthosCommerce\Feed\Service\Tracking\YourProductTypeItemPriceResolver</item>
        </argument>
    </arguments>
</type>
```

Then add to `app/code/Vendor/Module/Service/Tracking` directory your custom class, 
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
