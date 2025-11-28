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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\ResourceModel;

use AthosCommerce\Feed\Model\IndexingEntity as IndexingEntityModel;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Serialize\SerializerInterface;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Model\ResourceModel\Task\Error\DeleteErrors;
use AthosCommerce\Feed\Model\ResourceModel\Task\Error\SaveError;
use AthosCommerce\Feed\Model\Task as TaskModel;

class IndexingEntity extends AbstractDb
{
    const TABLE = 'athoscommerce_indexing_entity';
    const ID_FIELD_NAME = 'entity_id';

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            static::TABLE,
            static::ID_FIELD_NAME,
        );
    }

    /**
     * @param IndexingEntity $object
     *
     * @return AbstractDb
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $lastAction = $object->getData(IndexingEntityModel::LAST_ACTION);
        $object->setData(
            IndexingEntityModel::LAST_ACTION,
            $lastAction->value,
        );
        $nextAction = $object->getData(IndexingEntityModel::NEXT_ACTION);
        $object->setData(
            IndexingEntityModel::NEXT_ACTION,
            $nextAction->value,
        );

        return parent::_beforeSave($object);
    }

    /**
     * @param AbstractModel $object
     *
     * @return AbstractDb
     * @throws \Exception
     */
    protected function _afterLoad(AbstractModel $object)
    {
        if ($object->getId()) {
            $this->castPropertiesToCorrectType($object);
        }
        return $this;
    }

    /**
     * @param AbstractModel $object
     *
     * @return AbstractDb
     * @throws \Exception
     */
    protected function _afterSave(AbstractModel $object)
    {
        return parent::_afterSave($object);
    }
}
