<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\ViewModel\PdpViewModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PdpViewModelTest extends TestCase
{
    private $configMock;
    private $registryMock;
    private $configurableMock;
    private $groupedMock;
    private $storeManagerMock;
    private $serializerMock;
    private $loggerMock;
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->configurableMock = $this->createMock(Configurable::class);
        $this->groupedMock = $this->createMock(Grouped::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->viewModel = new PdpViewModel(
            $this->configMock,
            $this->registryMock,
            $this->configurableMock,
            $this->groupedMock,
            $this->storeManagerMock,
            $this->serializerMock,
            $this->loggerMock
        );
    }

    public function testReturnsEmptyStringWhenRenderingDisabled(): void
    {
        $this->configMock
            ->expects($this->once())
            ->method('shouldRender')
            ->willReturn(false);

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    public function testReturnsEmptyStringWhenNoCurrentProduct(): void
    {
        $this->configMock
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock
            ->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn(null);

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    public function testReturnsSerializedProductData(): void
    {
        $product = $this->createMock(Product::class);

        $this->configMock
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getDataUsingMethod')
            ->with('entity_id')
            ->willReturn(10);
        $product->method('getSku')->willReturn('SKU-001');
        $product->method('getFinalPrice')->willReturn(99.99);

        $this->configurableMock
            ->method('getParentIdsByChild')
            ->with(10)
            ->willReturn([100]);

        $store = $this->getStoreMockup();

        $store->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $this->storeManagerMock
            ->method('getStore')
            ->willReturn($store);

        $expectedData = [
            'uid' => '10',
            'sku' => 'SKU-001',
            'parentId' => '100',
            'price' => 99.99,
            'currency' => 'USD',
        ];

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($expectedData)
            ->willReturn('serialized-json');

        $this->assertSame(
            'serialized-json',
            $this->viewModel->getProductPageData()
        );
    }

    public function testUsesRegularPriceWhenFinalPriceIsZero(): void
    {
        $product = $this->createMock(Product::class);

        $this->configMock->method('shouldRender')->willReturn(true);
        $this->registryMock->method('registry')->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getDataUsingMethod')->willReturn(10);
        $product->method('getSku')->willReturn('SKU');
        $product->method('getFinalPrice')->willReturn(0);
        $product->method('getPrice')->willReturn(12349.95);

        $this->configurableMock
            ->method('getParentIdsByChild')
            ->willReturn([]);

        $this->groupedMock
            ->method('getParentIdsByChild')
            ->willReturn([]);

        $store = $this->getStoreMockup();

        $store->method('getCurrentCurrencyCode')->willReturn('USD');

        $this->storeManagerMock
            ->method('getStore')
            ->willReturn($store);

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($this->callback(
                fn(array $data) => $data['price'] === 12349.95
            ))
            ->willReturn('serialized-json');

        $this->viewModel->getProductPageData();
    }

    public function testUsesGroupedParentWhenConfigurableParentDoesNotExist(): void
    {
        $product = $this->createMock(Product::class);

        $this->configMock
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getDataUsingMethod')->willReturn(10);
        $product->method('getSku')->willReturn('SKU-001');
        $product->method('getFinalPrice')->willReturn(99.99);

        $this->configurableMock
            ->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(10)
            ->willReturn([]);

        $this->groupedMock
            ->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(10)
            ->willReturn([200]);

        $store = $this->getStoreMockup();

        $store->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $this->storeManagerMock
            ->method('getStore')
            ->willReturn($store);

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($this->callback(
                fn(array $data) => $data['parentId'] === '200'
            ))
            ->willReturn('serialized-json');

        $this->viewModel->getProductPageData();
    }

    public function testReturnsNullParentIdWhenNoParentProductsExist(): void
    {
        $product = $this->createMock(Product::class);

        $this->configMock
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock
            ->method('registry')
            ->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getDataUsingMethod')->willReturn(10);
        $product->method('getSku')->willReturn('SKU-001');
        $product->method('getFinalPrice')->willReturn(99.99);

        $this->configurableMock
            ->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(10)
            ->willReturn([]);

        $this->groupedMock
            ->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(10)
            ->willReturn([]);

        $store = $this->getStoreMockup();

        $store->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $this->storeManagerMock
            ->method('getStore')
            ->willReturn($store);

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($this->callback(
                fn(array $data) => $data['parentId'] === null
            ))
            ->willReturn('serialized-json');

        $this->viewModel->getProductPageData();
    }

    public function testReturnsNullCurrencyAndLogsErrorWhenStoreThrowsException(): void
    {
        $product = $this->createMock(Product::class);

        $this->configMock->method('shouldRender')->willReturn(true);
        $this->registryMock->method('registry')->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getDataUsingMethod')->willReturn(10);
        $product->method('getSku')->willReturn('SKU');
        $product->method('getFinalPrice')->willReturn(10);

        $this->configurableMock->method('getParentIdsByChild')->willReturn([]);
        $this->groupedMock->method('getParentIdsByChild')->willReturn([]);

        $this->storeManagerMock
            ->method('getStore')
            ->willThrowException(
                new \Magento\Framework\Exception\NoSuchEntityException(__('Store not found'))
            );

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Store not found');

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($this->callback(
                fn(array $data) => $data['currency'] === null
            ))
            ->willReturn('serialized-json');

        $this->viewModel->getProductPageData();
    }

    /**
     * @return (\Magento\Store\Api\Data\StoreInterface&MockObject)|MockObject
     */
    private function getStoreMockup()
    {
        return $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCurrentCurrencyCode'])
            ->getMock();
    }
}
