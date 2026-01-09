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

namespace AthosCommerce\Feed\Model\Group;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;

class DefaultGroupByAttributeResolver implements GroupByAttributeResolverInterface
{
    /**
     * Tracks seen attribute values per parent
     */
    private array $seenValues = [];

    /**
     * Can be extended via DI or plugins
     */
    private array $attributesToConsider;

    /**
     * @var bool
     */
    private bool $onlySwatchAttributes;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param AthosCommerceLogger $logger
     * @param array $attributesToConsider
     * @param bool $onlySwatchAttributes
     */
    public function __construct(
        AthosCommerceLogger $logger,
        array               $attributesToConsider = ['color'],
        bool                $onlySwatchAttributes = true
    )
    {
        $this->logger = $logger;
        $this->attributesToConsider = $attributesToConsider;
        $this->onlySwatchAttributes = $onlySwatchAttributes;
    }

    /**
     * @inheritDoc
     */
    public function isGroupable(
        Product $simple,
        Product $parent,
        array   $product
    ): bool
    {
        /** @var ConfigurableType $typeInstance */
        $typeInstance = $parent->getTypeInstance();
        $attributes = $typeInstance->getConfigurableAttributes($parent);

        $parentId = (int)$parent->getId();

        $this->logger->info(
            '[GroupBySwatch] Checking attribute for grouping: ',
            [
                'parent_id' => $parentId,
                'simple_id' => (int)$simple->getId(),
                'attributes' => $this->attributesToConsider
            ]
        );

        foreach ($attributes as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            if (!$productAttribute) {
                continue;
            }

            $attrCode = $productAttribute->getAttributeCode();

            /**
             * Skip attributes that are not in the configured list
             * Grouping is FIRST-COME-FIRST-WIN
             * ONLY based on configured attributes (color for now)
             */
            if (!in_array($attrCode, $this->attributesToConsider, true)) {
                continue;
            }

            if ($this->onlySwatchAttributes && !$productAttribute->getSwatchInputType()) {
                continue;
            }

            $label = $simple->getAttributeText($attrCode)
                ?? $simple->getData($attrCode);

            if ($label === null || $label === '') {
                continue;
            }

            if (is_array($label)) {
                $label = implode(',', $label);
            }
            $label = trim((string)$label);

            $this->seenValues[$parentId][$attrCode] ??= [];

            if (isset($this->seenValues[$parentId][$attrCode][$label])) {
                return false;
            }

            $this->seenValues[$parentId][$attrCode][$label] = true;
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->seenValues = [];
    }
}
