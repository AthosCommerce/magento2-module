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

namespace AthosCommerce\Feed\Model\Feed\Collection;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;

class AttributesModifier implements ModifierInterface
{
    /**
     * @var array
     */
    private $appliedAttributes;
    /**
     * @var AttributesProviderInterface
     */
    private $attributesProvider;

    /**
     * AttributesModifier constructor.
     * @param AttributesProviderInterface $attributesProvider
     * @param array $appliedAttributes
     */
    public function __construct(
        AttributesProviderInterface $attributesProvider,
        array $appliedAttributes = []
    ) {
        $this->appliedAttributes = $appliedAttributes;
        $this->attributesProvider = $attributesProvider;
    }

    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     * @return Collection
     */
    public function modify(Collection $collection, FeedSpecificationInterface $feedSpecification): Collection
    {
        $productAttributes = $this->attributesProvider->getAttributes($feedSpecification);
        $codes = [];
        foreach ($productAttributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if (!in_array($code, $this->appliedAttributes)) {
                $codes[] = $code;
            }
        }

        $collection->addAttributeToSelect($codes);

        return $collection;
    }
}
