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

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\SpecificSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ValueProcessor
{
    private $cache = [];

    private $sourceAttributes = [];
    /**
     * @param Attribute $attribute
     * @param $value
     * @return bool|string|null
     * @throws LocalizedException
     * @throws Exception
     */
    public function getValue(Attribute $attribute, $value, Product $product)
    {
        $key = null;
        $code = $attribute->getAttributeCode();
        if (!is_object($value) && !is_array($value) && $this->isSourceAttribute($attribute)) {
            $key = $code . '_' . $value;
            if (isset($this->cache[$key])) {
                return $this->cache[$key];
            }
        }

        $result = null;
        if ($this->isSourceAttribute($attribute)) {
            $source = $attribute->getSource();
            if ($source instanceof SpecificSourceInterface) {
                $sourceClone = clone $source;
                $sourceClone->getOptionsFor($product);
                $result = $sourceClone->getOptionText($value);
            } else {
                $result = $source->getOptionText($value);
            }
        } else {
            $result = $value;
        }

        if (is_object($result)) {
            if ($result instanceof Phrase) {
                $result = $result->getText();
            } else {
                throw new Exception("Unknown value object type " . get_class($result));
            }
        }

        if ($key) {
            $this->cache[$key] = $result;
        }

        return $result;
    }

    /**
     *
     */
    public function reset() : void
    {
        $this->cache = [];
        $this->sourceAttributes = [];
    }

    /**
     * @param Attribute $attribute
     * @return bool
     */
    private function isSourceAttribute(Attribute $attribute) : bool
    {
        $code = $attribute->getAttributeCode();
        if (!array_key_exists($code, $this->sourceAttributes)) {
            $this->sourceAttributes[$code] = $attribute->usesSource();
        }

        return $this->sourceAttributes[$code];
    }
}
