<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Customer\Model\Group;
use Magento\Framework\App\Area;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

require __DIR__ . '/grouped_products.php';
Bootstrap::getInstance()->loadArea(Area::AREA_ADMINHTML);
/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
/** @var CatalogRuleRepositoryInterface $ruleRepository */
$ruleRepository = $objectManager->get(CatalogRuleRepositoryInterface::class);
$rule = $objectManager->get(RuleFactory::class)->create();
$rule->loadPost(
    [
        'name' => 'AthosCommerce Test Grouped Rule For Child 1000',
        'is_active' => '1',
        'stop_rules_processing' => 0,
        'website_ids' => [$websiteRepository->get('base')->getId()],
        'customer_group_ids' => Group::NOT_LOGGED_IN_ID,
        'discount_amount' => 100,
        'simple_action' => 'by_fixed',
        'from_date' => '',
        'to_date' => '',
        'sort_order' => 100,
        'sub_is_enable' => 0,
        'sub_discount_amount' => 0,
        'conditions' => [
            '1' => ['type' => Combine::class, 'aggregator' => 'all', 'value' => '1', 'new_child' => ''],
            '1--1' => ['type' => Rule\Condition\Product::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'athoscommerce_grouped_test_simple_1000'],
        ],
    ]
);
$ruleRepository->save($rule);

$rule = $objectManager->get(RuleFactory::class)->create();
$rule->loadPost(
    [
        'name' => 'AthosCommerce Test Grouped Rule For Child 1010',
        'is_active' => '1',
        'stop_rules_processing' => 0,
        'website_ids' => [$websiteRepository->get('base')->getId()],
        'customer_group_ids' => Group::NOT_LOGGED_IN_ID,
        'discount_amount' => 100,
        'simple_action' => 'by_fixed',
        'from_date' => '',
        'to_date' => '',
        'sort_order' => 100,
        'sub_is_enable' => 0,
        'sub_discount_amount' => 0,
        'conditions' => [
            '1' => ['type' => Combine::class, 'aggregator' => 'all', 'value' => '1', 'new_child' => ''],
            '1--1' => ['type' => Rule\Condition\Product::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'athoscommerce_grouped_test_simple_1010'],
        ],
    ]
);
$ruleRepository->save($rule);

$rule = $objectManager->get(RuleFactory::class)->create();
$rule->loadPost(
    [
        'name' => 'AthosCommerce Test Grouped Rule For Child 1011',
        'is_active' => '1',
        'stop_rules_processing' => 0,
        'website_ids' => [$websiteRepository->get('base')->getId()],
        'customer_group_ids' => Group::NOT_LOGGED_IN_ID,
        'discount_amount' => 200,
        'simple_action' => 'by_fixed',
        'from_date' => '',
        'to_date' => '',
        'sort_order' => 100,
        'sub_is_enable' => 0,
        'sub_discount_amount' => 0,
        'conditions' => [
            '1' => ['type' => Combine::class, 'aggregator' => 'all', 'value' => '1', 'new_child' => ''],
            '1--1' => ['type' => Rule\Condition\Product::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'athoscommerce_grouped_test_simple_1011'],
        ],
    ]
);
$ruleRepository->save($rule);

/** @var IndexBuilder $indexBuilder */
$indexBuilder = $objectManager->get(IndexBuilder::class);
$indexBuilder->reindexFull();

$objectManager->get(IndexerRegistry::class)
    ->get('catalog_product_price')
    ->reindexAll();
