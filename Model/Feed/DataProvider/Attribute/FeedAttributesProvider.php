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

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

class FeedAttributesProvider implements AttributesProviderInterface
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductAttributeInterface[]
     */
    private $attributes = null;

    /**
     * FeedAttributesProvider constructor.
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return ProductAttributeInterface[]
     */
    public function getAttributes(FeedSpecificationInterface $feedSpecification): array
    {
        if (is_null($this->attributes)) {
            $restrictedAttributes = $feedSpecification->getIgnoreFields();
            if (!empty($restrictedAttributes)) {
                $this->searchCriteriaBuilder->addFilter(
                    ProductAttributeInterface::ATTRIBUTE_CODE,
                    $restrictedAttributes,
                    'nin'
                );
            }

            $searchCriteria = $this->searchCriteriaBuilder
                ->create();
            $this->attributes = $this->productAttributeRepository->getList($searchCriteria)->getItems();
        }
        return $this->attributes;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function getAttributeCodes(FeedSpecificationInterface $feedSpecification): array
    {
        $attributes = $this->getAttributes($feedSpecification);
        $codes = array_map(function ($attribute) {
            return $attribute->getAttributeCode();
        }, $attributes);

        return $codes;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->attributes = null;
    }
}
