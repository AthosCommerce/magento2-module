<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Helper;

use AthosCommerce\Feed\Api\Data\CustomersDataInterface;
use AthosCommerce\Feed\Api\Data\CustomersDataInterfaceFactory;
use AthosCommerce\Feed\Helper\Customer;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    /** @var CollectionFactory&MockObject */
    private $collectionFactory;

    /** @var CustomersDataInterfaceFactory&MockObject */
    private $customersDataFactory;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->customersDataFactory = $this->createMock(CustomersDataInterfaceFactory::class);
    }

    public function testGetCustomersAppliesDateAndRowRangeAndMapsPhoneSafely(): void
    {
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where', 'limit'])
            ->getMock();
        $select->expects($this->once())->method('where');
        $select->expects($this->once())->method('limit')->with(2, 0);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->exactly(2))
            ->method('addBindParam')
            ->withConsecutive([':from', '2026-08-01'], [':to', '2026-08-11']);
        $collection->method('getSelect')->willReturn($select);

        $itemWithPhone = $this->createConfiguredMock(CustomerModel::class, [
            'getId' => 1,
            'getEmail' => 'a@test.com',
        ]);
        $address = $this->createConfiguredMock(Address::class, ['getTelephone' => '12345']);
        $itemWithPhone->method('getPrimaryBillingAddress')->willReturn($address);

        $itemWithoutPhone = $this->createConfiguredMock(CustomerModel::class, [
            'getId' => 2,
            'getEmail' => 'b@test.com',
        ]);
        $itemWithoutPhone->method('getPrimaryBillingAddress')->willReturn(null);

        $collection->method('getItems')->willReturn([$itemWithPhone, $itemWithoutPhone]);
        $this->collectionFactory->method('create')->willReturn($collection);

        $data1 = $this->createMock(CustomersDataInterface::class);
        $data1->expects($this->once())->method('setId')->with(1);
        $data1->expects($this->once())->method('setEmail')->with('a@test.com');
        $data1->expects($this->once())->method('setPhoneNumber')->with('12345');

        $data2 = $this->createMock(CustomersDataInterface::class);
        $data2->expects($this->once())->method('setId')->with(2);
        $data2->expects($this->once())->method('setEmail')->with('b@test.com');
        $data2->expects($this->once())->method('setPhoneNumber')->with('');

        $this->customersDataFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($data1, $data2);

        $helper = new Customer($this->collectionFactory, $this->customersDataFactory);
        $result = $helper->getCustomers('2026-08-01,2026-08-10', '1,2');

        $this->assertCount(2, $result);
    }

    public function testGetCustomersTotalCountAppliesDateRangeAndReturnsSize(): void
    {
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where'])
            ->getMock();
        $select->expects($this->once())->method('where');

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('addBindParam')->with(':from', '2026-08-01');
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getSize')->willReturn(42);
        $this->collectionFactory->method('create')->willReturn($collection);

        $helper = new Customer($this->collectionFactory, $this->customersDataFactory);
        $total = $helper->getCustomersTotalCount('2026-08-01');

        $this->assertSame(42, $total);
    }
}

