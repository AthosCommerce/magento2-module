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

namespace AthosCommerce\Feed\Test\Unit\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Validation\ValidationResult;
use AthosCommerce\Feed\Api\Data\TaskInterfaceFactory;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Exception\UniqueTaskException;
use AthosCommerce\Feed\Exception\ValidationException;
use AthosCommerce\Feed\Model\CreateTask;
use AthosCommerce\Feed\Model\Task;
use AthosCommerce\Feed\Model\Task\TypeList;
use AthosCommerce\Feed\Model\Task\UniqueCheckerInterface;
use AthosCommerce\Feed\Model\Task\UniqueCheckerPool;
use AthosCommerce\Feed\Model\Task\ValidatorInterface;
use AthosCommerce\Feed\Model\Task\ValidatorPool;

class CreateTaskTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepositoryMock;

    /**
     * @var TaskInterfaceFactory
     */
    private $taskFactoryMock;

    /**
     * @var ValidatorPool
     */
    private $validatorPoolMock;

    /**
     * @var TypeList
     */
    private $typeListMock;

    /**
     * @var UniqueCheckerPool
     */
    private $uniqueCheckerPoolMock;

    private $createTask;
    /**
     * @var Manager
     */
    private $moduleManagerPoolMock;

    public function setUp(): void
    {
        $this->taskRepositoryMock = $this->createMock(TaskRepositoryInterface::class);
        $this->taskFactoryMock = $this->createMock(TaskInterfaceFactory::class);
        $this->validatorPoolMock = $this->createMock(ValidatorPool::class);
        $this->typeListMock = $this->createMock(TypeList::class);
        $this->uniqueCheckerPoolMock = $this->createMock(UniqueCheckerPool::class);
        $this->moduleManagerPoolMock = $this->createMock(Manager::class);
        $this->createTask = new CreateTask(
            $this->taskRepositoryMock,
            $this->taskFactoryMock,
            $this->validatorPoolMock,
            $this->typeListMock,
            $this->uniqueCheckerPoolMock,
            $this->moduleManagerPoolMock
        );
    }

    public function testExecute()
    {
        $type = 'type';
        $payload = [];

        $this->typeListMock->expects($this->once())
            ->method('exist')
            ->willReturn(true);
        // Mock isMsiEnabled() to return true
        $this->moduleManagerPoolMock->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true); // Add this line


        $validationResultMock = $this->getMockBuilder(ValidationResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validatorMock = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willReturn($validatorMock);
        $validatorMock->expects($this->once())
            ->method('validate')
            ->with($payload)
            ->willReturn($validationResultMock);
        $validationResultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $uniqueCheckerMock = $this->getMockBuilder(UniqueCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->uniqueCheckerPoolMock->expects($this->once())
            ->method('get')
            ->willReturn($uniqueCheckerMock);
        $uniqueCheckerMock->expects($this->once())
            ->method('check')
            ->with($payload)
            ->willReturn(true);

        $taskMock = $this->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->taskFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($taskMock);
        $taskMock->expects($this->once())
            ->method('setType')
            ->with($type)
            ->willReturnSelf();
        $taskMock->expects($this->once())
            ->method('setPayload')
            ->with($payload)
            ->willReturnSelf();
        $taskMock->expects($this->once())
            ->method('setStatus')
            ->with('pending')
            ->willReturnSelf();
        $this->taskRepositoryMock->expects($this->once())
            ->method('save')
            ->willReturn($taskMock);

        $this->assertSame($taskMock, $this->createTask->execute($type, $payload));
    }

    public function testExecuteExceptionCase()
    {
        $type = 'testType';
        $this->expectException(\Exception::class);
        $this->createTask->execute($type, '');
    }

    public function testExecuteValidationExceptionCase()
    {
        $type = 'testType';
        $this->typeListMock->expects($this->any())
            ->method('exist')
            ->with($type)
            ->willReturn(false);
        $this->expectException(ValidationException::class);
        $this->createTask->execute($type, []);
    }

    public function testExecuteValidationExceptionOnValidationCase()
    {
        $type = 'testType';
        $validationResultMock = $this->createMock(ValidationResult::class);
        $validatorMock = $this->createMock(ValidatorInterface::class);
        $this->typeListMock->expects($this->any())
            ->method('exist')
            ->with($type)
            ->willReturn(true);
        $this->validatorPoolMock->expects($this->any())
            ->method('get')
            ->with($type)
            ->willReturn($validatorMock);
        $validatorMock->expects($this->any())
            ->method('validate')
            ->with([])
            ->willReturn($validationResultMock);
        $validationResultMock->expects($this->any())
            ->method('isValid')
            ->willReturn(false);
        $validationResultMock->expects($this->any())
            ->method('getErrors')
            ->willReturn(['error']);
        $this->expectException(ValidationException::class);
        $this->createTask->execute($type, []);
    }


    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function testExecuteValidationExceptionOnUniqueTaskCase()
    {
        $type = 'testType';
        $uniqueCheckerInterfaceMock = $this->createMock(UniqueCheckerInterface::class);
        $validationResultMock = $this->createMock(ValidationResult::class);
        $validatorMock = $this->createMock(ValidatorInterface::class);
        $this->typeListMock->expects($this->once())
            ->method('exist')
            ->with($type)
            ->willReturn(true);
        $this->validatorPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willReturn($validatorMock);
        $validatorMock->expects($this->once())
            ->method('validate')
            ->with([])
            ->willReturn($validationResultMock);
        $validationResultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->uniqueCheckerPoolMock->expects($this->once())
            ->method('get')
            ->with($type)
            ->willReturn($uniqueCheckerInterfaceMock);
        $uniqueCheckerInterfaceMock->expects($this->once())
            ->method('check')
            ->with([])
            ->willReturn(false);
        $this->expectException(UniqueTaskException::class);
        $this->createTask->execute($type, []);
    }
}
