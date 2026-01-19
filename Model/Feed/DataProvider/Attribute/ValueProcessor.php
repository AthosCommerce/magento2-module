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

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as MagentoEavAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\SpecificSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessorInterface;

class ValueProcessor implements ValueProcessorInterface
{
    /**
     * @var Json
     */
    private $json;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param Json $json
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        Json                $json,
        AthosCommerceLogger $logger
    )
    {
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * @var array
     */
    private $optionCache = [];

    /**
     * @var array
     */
    private $sourceAttributeCache = [];

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->optionCache = [];
        $this->sourceAttributeCache = [];
    }

    /**
     * @param MagentoEavAttribute $attribute
     * @param $value
     * @param Product $product
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return
     */
    public function getValue(
        MagentoEavAttribute        $attribute,
                                   $value,
        Product                    $product,
        FeedSpecificationInterface $feedSpecification
    )
    {
        $attributeCode = $attribute->getAttributeCode();

        if (!$this->isSourceAttribute($attribute)) {
            if ($value instanceof Phrase) {
                return $value->getText();
            }

            if (is_scalar($value) || $value === null) {
                return $value;
            }

            if (is_array($value)) {
                return $this->json->serialize($value);
            }

            $this->logger->error(
                'Unexpected non-scalar value: ',
                [
                    'method' => __METHOD__,
                    'entityId' => $product->getEntityId(),
                    'code' => $attributeCode,
                    'type' => gettype($result),
                    'value' => $value,
                ],
            );

            return $value;
        }

        $storeId = (int)$product->getStoreId();

        if (!isset($this->optionCache[$storeId][$attributeCode])) {
            $this->optionCache[$storeId][$attributeCode] =
                $this->loadAttributeOptions(
                    $attribute,
                    $product
                );
        }

        $optionMap = $this->optionCache[$storeId][$attributeCode];

        if ($attribute->getFrontendInput() === 'multiselect') {
            $values = $this->sanitizeMultiselect($value);

            $labels = [];
            foreach ($values as $value) {
                $labels[] = $optionMap[(string)$value] ?? null;
            }

            return implode(
                $feedSpecification->getMultiValuedSeparator(),
                array_filter($labels)
            );
        }

        if (!isset($optionMap[(string)$value])) {
            return $value;
        }

        return $optionMap[(string)$value] ?? null;
    }

    /**
     * @param MagentoEavAttribute $attribute
     *
     * @return bool
     */
    private function isSourceAttribute(MagentoEavAttribute $attribute): bool
    {
        $code = $attribute->getAttributeCode();

        if (!array_key_exists($code, $this->sourceAttributeCache)) {
            $this->sourceAttributeCache[$code] = (bool)$attribute->usesSource();
        }

        return $this->sourceAttributeCache[$code];
    }

    /**
     * @param $value
     *
     * @return array
     */
    private function sanitizeMultiselect($value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        return explode(',', (string)$value);
    }

    /**
     * @param MagentoEavAttribute $attribute
     * @param Product $product
     *
     * @return array
     * @throws LocalizedException
     */
    private function loadAttributeOptions(
        MagentoEavAttribute $attribute,
        Product             $product
    ): array
    {
        $source = $attribute->getSource();

        // SpecificSourceInterface requires per-product option set
        if ($source instanceof SpecificSourceInterface) {
            $sourceClone = clone $source;
            $sourceClone->getOptionsFor($product);
            $options = $sourceClone->getAllOptions();
        } else {
            $options = $source->getAllOptions();
        }

        $optionMaps = [];

        foreach ($options as $option) {
            if (!isset($option['value'])) {
                continue;
            }

            $value = (string)$option['value'];
            $label = $option['label'] instanceof Phrase
                ? $option['label']->getText()
                : (string)$option['label'];

            $optionMaps[$value] = $label;
        }

        return $optionMaps;
    }
}
