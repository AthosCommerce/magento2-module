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

namespace AthosCommerce\Feed\Model\Feed;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Collection\ModifierInterface;

class CollectionProvider implements CollectionProviderInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var ModifierInterface[]
     */
    private $modifiers;

    /**
     * CollectionProvider constructor.
     * @param CollectionFactory $collectionFactory
     * @param array $modifiers
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        array $modifiers = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->modifiers = $modifiers;
    }

    /**
     * @param FeedSpecificationInterface $specification
     * @return Collection
     * @throws \Exception
     */
    public function getCollection(FeedSpecificationInterface $specification): Collection
    {
        $collection = $this->collectionFactory->create();
        $modifiers = $this->sort($this->modifiers);
        foreach ($modifiers as $key => $modifierData) {
            /** @var ModifierInterface $modifier */
            $modifier = $modifierData['objectInstance'] ?? null;
            if (!$modifier) {
                throw new \Exception((string) __('No objectInstance for modifier %1', $key));
            }
            $collection = $modifier->modify($collection, $specification);
        }

        return $collection;
    }

    /**
     * @param array $data
     * @return array
     */
    private function sort(array $data)
    {
        usort($data, function (array $a, array $b) {
            return $this->getSortOrder($a) <=> $this->getSortOrder($b);
        });

        return $data;
    }

    /**
     * @param array $variable
     * @return int
     */
    private function getSortOrder(array $variable)
    {
        return !empty($variable['sortOrder']) ? (int) $variable['sortOrder'] : 0;
    }
}
