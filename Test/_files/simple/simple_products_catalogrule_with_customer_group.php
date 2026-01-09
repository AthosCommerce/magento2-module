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
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\App\Area;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

require __DIR__ . '/01_simple_products.php';
Bootstrap::getInstance()->loadArea(Area::AREA_ADMINHTML);
/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
/** @var CatalogRuleRepositoryInterface $ruleRepository */
$ruleRepository = $objectManager->get(CatalogRuleRepositoryInterface::class);
/** @var Rule $rule */
$rule = $objectManager->get(RuleFactory::class)->create();
$rule->loadPost(
    [
        'name' => 'AthosCommerce Test Simple CG Percent rule for simple product',
        'is_active' => '1',
        'stop_rules_processing' => 0,
        'website_ids' => [$websiteRepository->get('base')->getId()],
        'customer_group_ids' => 1,
        'discount_amount' => 30,
        'simple_action' => 'by_percent',
        'from_date' => '',
        'to_date' => '',
        'sort_order' => 0,
        'sub_is_enable' => 0,
        'sub_discount_amount' => 0,
        'conditions' => [
            '1' => ['type' => Combine::class, 'aggregator' => 'all', 'value' => '1', 'new_child' => ''],
            '1--1' => ['type' => Rule\Condition\Product::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'athoscommerce_simple_1'],
        ],
    ]
);
$ruleRepository->save($rule);

$rule = $objectManager->get(RuleFactory::class)->create();
$rule->loadPost(
    [
        'name' => 'AthosCommerce Test Simple CG Rule For Product 2',
        'is_active' => '1',
        'stop_rules_processing' => 0,
        'website_ids' => [$websiteRepository->get('base')->getId()],
        'customer_group_ids' => 1,
        'discount_amount' => 8,
        'simple_action' => 'by_fixed',
        'from_date' => '',
        'to_date' => '',
        'sort_order' => 100,
        'sub_is_enable' => 0,
        'sub_discount_amount' => 0,
        'conditions' => [
            '1' => ['type' => Combine::class, 'aggregator' => 'all', 'value' => '1', 'new_child' => ''],
            '1--1' => ['type' => Rule\Condition\Product::class, 'attribute' => 'sku', 'operator' => '==', 'value' => 'athoscommerce_simple_2'],
        ],
    ]
);
$ruleRepository->save($rule);
