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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

class ChildAttributesProvider
{
    /**
     * @var Attribute[]|null
     */
    private $specificationAttributes;
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * ChildAttributesProvider constructor.
     * @param Config $eavConfig
     */
    public function __construct(
        Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return Attribute[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributes(FeedSpecificationInterface $feedSpecification) : array
    {
        if (is_null($this->specificationAttributes)) {
            $childFields = $feedSpecification->getChildFields();
            $specificationAttributes = [];
            foreach ($childFields as $attribute) {
                $productAttribute = $this->eavConfig->getAttribute("catalog_product", $attribute);
                if ($productAttribute && !isset($result[$productAttribute->getAttributeId()])) {
                    $specificationAttributes[$productAttribute->getAttributeId()] = $productAttribute;
                }
            }

            $this->specificationAttributes = $specificationAttributes;
        }

        return $this->specificationAttributes;
    }

    /**
     *
     */
    public function reset() : void
    {
        $this->specificationAttributes = null;
    }
}
