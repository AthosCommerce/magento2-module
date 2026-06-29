<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Catalog\Model\Product;

class ParentIdSourceFieldEvaluator
{
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var array
     */
    private $supportedInputTypes;

    /**
     * @param AthosCommerceLogger $logger
     * @param array $supportedInputTypes
     */
    public function __construct(
        AthosCommerceLogger $logger,
        array               $supportedInputTypes = []
    )
    {
        $this->logger = $logger;
        $this->supportedInputTypes = array_unique(array_merge(
            ['text', 'select', 'multiselect', 'hidden', 'weight', 'number', 'price'],
            $supportedInputTypes
        ));
    }

    /**
     * @param Product $product
     * @param string|null $identifier
     * @return string|null
     */
    public function execute(Product $product, ?string $identifier): ?string
    {
        $identifier = trim((string)$identifier);

        // If config is blank, fall back to entity_id first, then row_id
        if ($identifier === '') {
            $value = $product->getDataUsingMethod('entity_id');

            if ($value === null || $value === '') {
                $value = $product->getDataUsingMethod('row_id');
            }

            return ($value === null || $value === '') ? null : (string)$value;
        }

        // Handle direct product fields
        if (in_array($identifier, ['row_id', 'entity_id', 'sku'], true)) {
            $value = $product->getDataUsingMethod($identifier);
            return ($value === null || $value === '') ? null : (string)$value;
        }

        if (!method_exists($product, 'getResource')) {
            return null;
        }

        $attribute = $product->getResource()->getAttribute($identifier);

        if (!$attribute) {
            $this->logger->warning(sprintf(
                "Feed Config Issue: The parent ID source field '%s' does not exist in Magento. Check feed mapping.",
                $identifier
            ));
            return null;
        }

        $inputType = $attribute->getFrontendInput();
        if (!in_array($inputType, $this->supportedInputTypes, true)) {
            $this->logger->debug(
                sprintf(
                    "Feed Config Issue: The attribute '%s' has an input type of '%s', which is not supported.",
                    $identifier,
                    $inputType
                ),
                [
                    'supported types' => $this->supportedInputTypes,
                    'sku' => $product->getSku(),
                ]
            );
            return null;
        }

        $value = $product->getAttributeText($identifier);

        if ($value === false || $value === null || $value === '') {
            $value = $product->getDataUsingMethod($identifier);
        }

        if ($value === null || $value === '') {
            $this->logger->info(sprintf(
                "Missing Data: The product (SKU: %s) does not have a value filled in for the parent ID field '%s'.",
                $product->getSku(),
                $identifier
            ));
            return null;
        }

        if (is_array($value)) {
            $firstValue = reset($value);

            if ($firstValue === false || $firstValue === null || $firstValue === '') {
                return null;
            }

            return trim((string)$firstValue);
        }

        $value = trim((string)$value);

        if ($value === '') {
            return null;
        }

        if (strpos($value, ',') !== false) {
            $exploded = explode(',', $value);
            return trim($exploded[0]);
        }

        return $value;
    }
}
