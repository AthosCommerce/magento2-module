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

namespace AthosCommerce\Feed\Test\Integration\Model;

use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity\Collection as IndexingEntityCollection;
use AthosCommerce\Feed\Api\Data\IndexingEntityInterface;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Test\Integration\Traits\ObjectInstantiationTrait;
use AthosCommerce\Feed\Model\Update\EntityInterface as EntityUpdateInterface;
use AthosCommerce\Feed\Model\Update\Entity as EntityUpdate;
use AthosCommerce\Feed\Model\Update\EntityFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AthosCommerce\Feed\Model\Update\Entity::class
 * @method EntityUpdateInterface instantiateTestObject(?array $arguments = null)
 * @method EntityUpdateInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class IndexingEntityTest extends TestCase
{
    use ObjectInstantiationTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = EntityUpdate::class;
        $this->interfaceFqcn = \AthosCommerce\Feed\Model\Update\EntityInterface::class;
        $this->constructorArgumentDefaults = [
            'data' => [
                'entityType' => '__CMS',
                'entityIds' => [1, 2, 3],


            ],
        ];
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @testWith ["entityType", "entity_type"]
     *           ["entityIds", "entity_ids"]
     */
    public function testObjectInstantiationFails_ForInvalidDataKeys(string $key, string $invalidKey): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid key provided in creation of %s. Key %s',
                EntityUpdate::class,
                $invalidKey,
            ),
        );

        $data = [
            'entityType' => '__CMS',
            'entityIds' => [1, 2, 3],
        ];
        $data[$invalidKey] = $data[$key];
        unset($data[$key]);

        $modelFactory = $this->objectManager->get(EntityFactory::class);
        $modelFactory->create([
            'data' => $data,
        ]);
    }
}
