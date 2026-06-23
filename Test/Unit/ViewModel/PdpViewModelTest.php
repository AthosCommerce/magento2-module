<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\IdProviderInterface;
use AthosCommerce\Feed\ViewModel\PdpViewModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PdpViewModelTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Registry|MockObject
     */
    private $registryMock;

    /**
     * @var IdProviderInterface|MockObject
     */
    private $idProviderMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var PdpViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->idProviderMock = $this->createMock(IdProviderInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->viewModel = new PdpViewModel(
            $this->configMock,
            $this->registryMock,
            $this->idProviderMock,
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

    public function testReturnsEmptyStringWhenCurrentProductHasNoId(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $this->configMock
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock
            ->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    public function testReturnsSerializedProductData(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $store = $this->getStoreMockup();

        $this->configMock
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getFinalPrice')->willReturn(99.99);

        $this->idProviderMock
            ->expects($this->once())
            ->method('getItemId')
            ->with($product)
            ->willReturn('10');

        $this->idProviderMock
            ->expects($this->once())
            ->method('getItemSku')
            ->with($product)
            ->willReturn('SKU-001');

        $this->idProviderMock
            ->expects($this->once())
            ->method('getItemParentId')
            ->with($product)
            ->willReturn('100');

        $store->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $this->storeManagerMock
            ->expects($this->once())
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

        $this->assertSame('serialized-json', $this->viewModel->getProductPageData());
    }

    public function testUsesRegularPriceWhenFinalPriceIsZero(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $store = $this->getStoreMockup();

        $this->configMock->method('shouldRender')->willReturn(true);
        $this->registryMock->method('registry')->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getFinalPrice')->willReturn(0);
        $product->method('getPrice')->willReturn(12349.95);

        $this->idProviderMock->method('getItemId')->willReturn('10');
        $this->idProviderMock->method('getItemSku')->willReturn('SKU');
        $this->idProviderMock->method('getItemParentId')->willReturn(null);

        $store->method('getCurrentCurrencyCode')->willReturn('USD');

        $this->storeManagerMock
            ->method('getStore')
            ->willReturn($store);

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function (array $data) {
                return $data['price'] === 12349.95;
            }))
            ->willReturn('serialized-json');

        $this->viewModel->getProductPageData();
    }

    public function testReturnsNullParentIdWhenNoParentProductExists(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $store = $this->getStoreMockup();

        $this->configMock
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock
            ->method('registry')
            ->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getFinalPrice')->willReturn(99.99);

        $this->idProviderMock->method('getItemId')->willReturn('10');
        $this->idProviderMock->method('getItemSku')->willReturn('SKU-001');
        $this->idProviderMock->method('getItemParentId')->willReturn(null);

        $store->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $this->storeManagerMock
            ->method('getStore')
            ->willReturn($store);

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function (array $data) {
                return $data['parentId'] === null;
            }))
            ->willReturn('serialized-json');

        $this->viewModel->getProductPageData();
    }

    public function testReturnsNullCurrencyAndLogsErrorWhenStoreThrowsException(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $this->configMock->method('shouldRender')->willReturn(true);
        $this->registryMock->method('registry')->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getFinalPrice')->willReturn(10.0);

        $this->idProviderMock->method('getItemId')->willReturn('10');
        $this->idProviderMock->method('getItemSku')->willReturn('SKU');
        $this->idProviderMock->method('getItemParentId')->willReturn(null);

        $this->storeManagerMock
            ->expects($this->once())
            ->method('getStore')
            ->willThrowException(
                new NoSuchEntityException(__('Store not found'))
            );

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Store not found');

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function (array $data) {
                return $data['currency'] === null;
            }))
            ->willReturn('serialized-json');

        $this->viewModel->getProductPageData();
    }

    public function testReturnsEmptyStringWhenSerializerReturnsNonString(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $store = $this->getStoreMockup();

        $this->configMock->method('shouldRender')->willReturn(true);
        $this->registryMock->method('registry')->willReturn($product);

        $product->method('getId')->willReturn(10);
        $product->method('getFinalPrice')->willReturn(50.00);

        $this->idProviderMock->method('getItemId')->willReturn('10');
        $this->idProviderMock->method('getItemSku')->willReturn('SKU');
        $this->idProviderMock->method('getItemParentId')->willReturn(null);

        $store->method('getCurrentCurrencyCode')->willReturn('USD');

        $this->storeManagerMock->method('getStore')->willReturn($store);

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->willReturn(null);

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    /**
     * @return Store|MockObject
     */
    private function getStoreMockup()
    {
        return $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCurrentCurrencyCode'])
            ->getMock();
    }
}
