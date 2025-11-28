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

namespace AthosCommerce\Feed\Model\ResourceModel\IndexingEntity;

use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Traits\CastIndexingEntityPropertiesToCorrectType;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    use CastIndexingEntityPropertiesToCorrectType;

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            IndexingEntity::class,
            IndexingEntityResourceModel::class,
        );
    }

    /**
     * Ensure returned IndexingEntity data is the correct type, by default all fields returned as strings
     *
     * @return $this|\AthosCommerce\Feed\Model\ResourceModel\IndexingEntity\Collection
     */
    protected function _afterLoad(
    ) // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint, PSR2.Methods.MethodDeclaration.Underscore, Generic.Files.LineLength.TooLong
    {
        parent::_afterLoad();

        /** @var IndexingEntity $item */
        foreach ($this->getItems() as $item) {
            $this->castPropertiesToCorrectType($item);
        }
        $this->_eventManager->dispatch(
            'athos_indexing_entity_collection_load_after',
            ['collection' => $this],
        );

        return $this;
    }
}
