<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Helper;

use AthosCommerce\Feed\Api\Data\CustomersDataInterface;
use AthosCommerce\Feed\Api\Data\CustomersDataInterfaceFactory;
use AthosCommerce\Feed\Helper\Customer;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
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
            ->onlyMethods(['where', 'joinLeft', 'order', 'limit'])
            ->getMock();
        $select->expects($this->once())->method('where');
        $select->expects($this->once())
            ->method('joinLeft')
            ->with(
                ['billing_address' => 'customer_address_entity'],
                'billing_address.entity_id = e.default_billing',
                ['billing_telephone' => 'telephone']
            )
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('order')
            ->with('e.entity_id ASC')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('limit')
            ->with(2, 0)
            ->willReturnSelf();

        $collection = $this->createMock(Collection::class);
        $bindCalls = 0;
        $collection->expects($this->exactly(2))
            ->method('addBindParam')
            ->willReturnCallback(
                function (string $key, string $value) use (&$bindCalls): void {
                    if ($bindCalls === 0) {
                        $this->assertSame(':from', $key);
                        $this->assertSame('2026-08-01', $value);
                    } else {
                        $this->assertSame(':to', $key);
                        $this->assertSame('2026-08-11', $value);
                    }
                    $bindCalls++;
                }
            );
        $collection->method('getSelect')->willReturn($select);
        $collection->expects($this->once())->method('getTable')->with('customer_address_entity')
            ->willReturn('customer_address_entity');

        $itemWithPhone = new class {
            public function getId(): int
            {
                return 1;
            }

            public function getEmail(): string
            {
                return 'a@test.com';
            }

            public function getData(string $key)
            {
                if ($key === 'billing_telephone') {
                    return '12345';
                }

                return null;
            }
        };

        $itemWithoutPhone = new class {
            public function getId(): int
            {
                return 2;
            }

            public function getEmail(): string
            {
                return 'b@test.com';
            }

            public function getData(string $key)
            {
                return null;
            }
        };

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
            ->onlyMethods(['where', 'order'])
            ->getMock();
        $select->expects($this->once())->method('where');
        $select->expects($this->once())->method('order')->with('e.entity_id ASC')->willReturnSelf();

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
