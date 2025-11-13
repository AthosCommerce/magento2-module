# AthosCommerce Feed module for Magento 2
This module generates product data feed.

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

###  Product Collection modification

- 

###  Product modification

- 
