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

use Magento\Catalog\Observer\SwitchPriceAttributeScopeOnConfigChange;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/second_website_with_store_group_and_store_rollback.php';
require __DIR__ . '/simple_products_rollback.php';
/** @var ReinitableConfigInterface $reinitiableConfig */
$reinitiableConfig = Bootstrap::getObjectManager()->get(
    ReinitableConfigInterface::class
);
$reinitiableConfig->setValue(
    'catalog/price/scope',
    Store::PRICE_SCOPE_GLOBAL
);
$observer = Bootstrap::getObjectManager()->get(
    Observer::class
);
Bootstrap::getObjectManager()->get(SwitchPriceAttributeScopeOnConfigChange::class)
    ->execute($observer);
