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

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class AssertChildProducts extends TestCase
{
    /**
     * @param array $products
     * @param array $config
     */
    public function assertChildProducts(array $products, array $config): void
    {
        $productsConfig = $config['products'] ?? [];
        $requiredAttributes = $config['required_attributes'] ?? [];
        $additionalAttributes = $config['additional_attributes'] ?? [];
        $restrictedAttributes = $config['restricted_attributes'] ?? [];
        foreach ($products as $product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $sku = $productModel->getSku();
            // its simple product
            if (!empty($productsConfig) && !isset($productsConfig[$sku])) {
                // check that simple product doesnt have any configurable product related keys
                $this->assertAttributesNotExist($product, $requiredAttributes);
            } else {
                $this->assertAttributesExist($product, $requiredAttributes);
                $this->assertAttributesExist($product, $additionalAttributes);
                $this->assertAttributesNotExist($product, $restrictedAttributes);

                $childCount = $productsConfig[$sku]['child_count'] ?? null;
                $skuPrefix = $productsConfig[$sku]['sku_prefix'] ?? null;
                $namePrefix = $productsConfig[$sku]['name_prefix'] ?? null;
                $valueMap = $productsConfig[$sku]['value_map'] ?? null;
                if (!is_null($childCount)) {
                    $this->assertCount((int)$childCount, $product['child_sku'] ?? []);
                }

                if (!is_null($skuPrefix)) {
                    $skus = $product['child_sku'] ?? [];
                    foreach ($skus as $childSku) {
                        $this->assertTrue(strpos($childSku, $skuPrefix) === 0);
                    }
                }

                if (!is_null($namePrefix)) {
                    $names = $product['child_name'] ?? [];
                    foreach ($names as $name) {
                        $this->assertTrue(strpos($name, $namePrefix) === 0);
                    }
                }

                if (!is_null($valueMap)) {
                    $this->assertValueMap($product, $valueMap);
                }
            }
        }
    }

    /**
     * @param array $data
     * @param array $valueMap
     */
    private function assertValueMap(array $data, array $valueMap): void
    {
        foreach ($valueMap as $field => $value) {
            $fieldValues = $data[$field] ?? [];
            foreach ($fieldValues as $fieldValue) {
                $this->assertTrue(in_array($fieldValue, $value));
                $key = array_search($fieldValue, $value);
                unset($value[$key]);
            }

            $this->assertEmpty($value);
        }
    }

    /**
     * @param array $data
     * @param array $attributes
     */
    private function assertAttributesExist(array $data, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->assertArrayHasKey($attribute, $data);
        }
    }

    /**
     * @param array $data
     * @param array $attributes
     */
    private function assertAttributesNotExist(array $data, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->assertArrayNotHasKey($attribute, $data);
        }
    }
}
