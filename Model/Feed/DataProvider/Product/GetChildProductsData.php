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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;

class GetChildProductsData
{
    /**
     * @var ValueProcessor
     */
    private $valueProcessor;

    /**
     * GetChildProductsData constructor.
     * @param ValueProcessor $valueProcessor
     */
    public function __construct(
        ValueProcessor $valueProcessor
    ) {
        $this->valueProcessor = $valueProcessor;
    }

    /**
     * @param array $productData
     * @param Product[] $childProducts
     * @param Attribute[] $attributes
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getProductData(
        array $productData,
        array $childProducts,
        array $attributes,
        FeedSpecificationInterface $feedSpecification
    ) : array {
        $result = [];
        $ignoredFields = $feedSpecification->getIgnoreFields();
        foreach($childProducts as $child) {
            foreach($attributes as $childAttribute) {
                $code = $childAttribute->getAttributeCode();
                if (in_array($code, $ignoredFields)) {
                    continue;
                }

                $value = $this->valueProcessor->getValue($childAttribute, $child->getData($code), $child);
                if ($value != '' && !empty($value)) {
                    $result[$code][] = $value;
                }
            }

            if (!in_array('child_sku', $ignoredFields) && $child->getSku() != '') {
                $result['child_sku'][] = $child->getSku();
            }

            if (!in_array('child_name', $ignoredFields) && $child->getName() != '') {
                $result['child_name'][] = $child->getName();
            }

            if($feedSpecification->getIncludeChildPrices() && !in_array('child_final_price', $ignoredFields)) {
                $price = $child->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getMinimalPrice()->getValue();
                $result['child_final_price'][] = $price;
            }
        }

        foreach ($result as $key => &$value) {
            if (isset($productData[$key])) {
                $productDataValue = is_array($productData[$key]) ? $productData[$key] : [$productData[$key]];
                $value = array_merge($productDataValue, $value);
            }
        }

        return $result;
    }
}
