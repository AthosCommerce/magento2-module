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

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider\Parent;

use Magento\Catalog\Model\Product;

/**
 * Shared assertion helpers for child-product data provider tests.
 *
 * Intended for use in PHPUnit TestCase subclasses only, because the methods
 * delegate to PHPUnit assertion methods (assertArrayHasKey, assertCount, etc.)
 * that are provided by the base class.
 */
trait ChildProductAssertionsTrait
{
    /**
     * Assert child-product data against a declarative config array.
     *
     * Supported config keys:
     *   - products           (array)  map of parent SKU → product expectations
     *   - required_attributes (array) keys that MUST be present
     *   - additional_attributes (array) extra keys that MUST be present
     *   - restricted_attributes (array) keys that MUST NOT be present
     *
     * Each entry under "products" may contain:
     *   - child_count  (int)    expected number of child SKUs
     *   - sku_prefix   (string) prefix each child SKU must start with
     *   - name_prefix  (string) prefix each child name must start with
     *   - value_map    (array)  map of field → expected values (order-independent)
     *
     * @param array $products
     * @param array $config
     */
    private function assertChildProducts(array $products, array $config): void
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

            // Simple product: must NOT carry parent-related keys.
            if (!empty($productsConfig) && !isset($productsConfig[$sku])) {
                $this->assertAttributesNotExist($product, $requiredAttributes);
                continue;
            }

            $this->assertAttributesExist($product, $requiredAttributes);
            $this->assertAttributesExist($product, $additionalAttributes);
            $this->assertAttributesNotExist($product, $restrictedAttributes);

            $childCount = $productsConfig[$sku]['child_count'] ?? null;
            $skuPrefix = $productsConfig[$sku]['sku_prefix'] ?? null;
            $namePrefix = $productsConfig[$sku]['name_prefix'] ?? null;
            $valueMap = $productsConfig[$sku]['value_map'] ?? null;

            if ($childCount !== null) {
                $this->assertCount((int)$childCount, $product['child_sku'] ?? []);
            }

            if ($skuPrefix !== null) {
                foreach ($product['child_sku'] ?? [] as $childSku) {
                    $this->assertTrue(strpos($childSku, $skuPrefix) === 0);
                }
            }

            if ($namePrefix !== null) {
                foreach ($product['child_name'] ?? [] as $name) {
                    $this->assertTrue(strpos($name, $namePrefix) === 0);
                }
            }

            if ($valueMap !== null) {
                $this->assertValueMap($product, $valueMap);
            }
        }
    }

    /**
     * Assert that every value in $valueMap appears in $data (order-independent,
     * each occurrence consumed exactly once).
     *
     * @param array $data
     * @param array $valueMap field → list of expected values
     */
    private function assertValueMap(array $data, array $valueMap): void
    {
        foreach ($valueMap as $field => $expected) {
            foreach ($data[$field] ?? [] as $fieldValue) {
                $this->assertTrue(in_array($fieldValue, $expected));
                $key = array_search($fieldValue, $expected);
                unset($expected[$key]);
            }
            $this->assertEmpty($expected);
        }
    }

    /**
     * Assert that every attribute in $attributes exists as a key in $data.
     *
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
     * Assert that none of the attributes in $attributes exist as keys in $data.
     *
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
