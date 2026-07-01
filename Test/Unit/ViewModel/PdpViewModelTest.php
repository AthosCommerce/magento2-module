<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\ViewModel;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Config;
use AthosCommerce\Feed\Service\Tracking\IdProviderInterface;
use AthosCommerce\Feed\ViewModel\PdpViewModel;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdpViewModel::class)]
final class PdpViewModelTest extends TestCase
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
     * @var IdProviderInterface
     */
    private $idProviderStub;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerStub;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var AthosCommerceLogger
     */
    private $loggerStub;

    /**
     * @var PdpViewModel
     */
    private $viewModel;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->idProviderStub = $this->createStub(IdProviderInterface::class);
        $this->storeManagerStub = $this->createStub(StoreManagerInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->loggerStub = $this->createStub(AthosCommerceLogger::class);

        $this->viewModel = new PdpViewModel(
            $this->configMock,
            $this->registryMock,
            $this->idProviderStub,
            $this->storeManagerStub,
            $this->serializerMock,
            $this->loggerStub
        );
    }

    public function testReturnsEmptyStringWhenRenderingDisabled(): void
    {
        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(false);

        $this->registryMock->expects($this->never())
            ->method('registry');

        $this->serializerMock->expects($this->never())
            ->method('serialize');

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    public function testReturnsEmptyStringWhenNoCurrentProduct(): void
    {
        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn(null);

        $this->serializerMock->expects($this->never())
            ->method('serialize');

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    public function testReturnsEmptyStringWhenCurrentProductHasNoId(): void
    {
        $product = $this->createProductMock();

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->serializerMock->expects($this->never())
            ->method('serialize');

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    public function testReturnsSerializedProductData(): void
    {
        $product = $this->createProductMock();
        $store = $this->createStoreMock();

        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $idProviderMock = $this->createMock(IdProviderInterface::class);

        $this->viewModel = new PdpViewModel(
            $this->configMock,
            $this->registryMock,
            $idProviderMock,
            $storeManagerMock,
            $this->serializerMock,
            $this->loggerStub
        );

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->expects($this->once())
            ->method('getId')
            ->willReturn(10);

        $product->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn(99.99);

        $idProviderMock->expects($this->once())
            ->method('getItemId')
            ->with($product)
            ->willReturn('10');

        $idProviderMock->expects($this->once())
            ->method('getItemSku')
            ->with($product)
            ->willReturn('SKU-001');

        $idProviderMock->expects($this->once())
            ->method('getItemParentId')
            ->with($product)
            ->willReturn('100');

        $storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $store->expects($this->once())
            ->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $expectedData = [
            'uid' => '100_10',
            'sku' => 'SKU-001',
            'parentId' => '100',
            'price' => 99.99,
            'currency' => 'USD',
        ];

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedData)
            ->willReturn('serialized-json');

        $this->assertSame('serialized-json', $this->viewModel->getProductPageData());
    }

    public function testUsesRegularPriceWhenFinalPriceIsZero(): void
    {
        $product = $this->createProductMock();
        $store = $this->createStoreMock();

        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $idProviderStub = $this->createStub(IdProviderInterface::class);

        $idProviderStub->method('getItemId')->willReturn('10');
        $idProviderStub->method('getItemSku')->willReturn('SKU');
        $idProviderStub->method('getItemParentId')->willReturn('10');

        $this->viewModel = new PdpViewModel(
            $this->configMock,
            $this->registryMock,
            $idProviderStub,
            $storeManagerMock,
            $this->serializerMock,
            $this->loggerStub
        );

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->expects($this->once())
            ->method('getId')
            ->willReturn(10);

        $product->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn(0.0);

        $product->expects($this->once())
            ->method('getPrice')
            ->willReturn(12349.95);

        $storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $store->expects($this->once())
            ->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($this->callback(static function (array $data): bool {
                return $data === [
                        'uid' => '10',
                        'sku' => 'SKU',
                        'parentId' => '10',
                        'price' => 12349.95,
                        'currency' => 'USD',
                    ];
            }))
            ->willReturn('serialized-json');

        $this->assertSame('serialized-json', $this->viewModel->getProductPageData());
    }

    public function testConvertsEmptyParentIdToNull(): void
    {
        $product = $this->createProductMock();
        $store = $this->createStoreMock();

        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $idProviderStub = $this->createStub(IdProviderInterface::class);

        $idProviderStub->method('getItemId')->willReturn('10');
        $idProviderStub->method('getItemSku')->willReturn('SKU-001');
        $idProviderStub->method('getItemParentId')->willReturn('');

        $this->viewModel = new PdpViewModel(
            $this->configMock,
            $this->registryMock,
            $idProviderStub,
            $storeManagerMock,
            $this->serializerMock,
            $this->loggerStub
        );

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->expects($this->once())
            ->method('getId')
            ->willReturn(10);

        $product->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn(99.99);

        $storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $store->expects($this->once())
            ->method('getCurrentCurrencyCode')
            ->willReturn('USD');

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($this->callback(static function (array $data): bool {
                return $data['parentId'] === '10';
            }))
            ->willReturn('serialized-json');

        $this->assertSame('serialized-json', $this->viewModel->getProductPageData());
    }

    public function testReturnsNullCurrencyAndLogsErrorWhenStoreThrowsException(): void
    {
        $product = $this->createProductMock();

        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $loggerMock = $this->createMock(AthosCommerceLogger::class);
        $idProviderStub = $this->createStub(IdProviderInterface::class);

        $idProviderStub->method('getItemId')->willReturn('10');
        $idProviderStub->method('getItemSku')->willReturn('SKU');
        $idProviderStub->method('getItemParentId')->willReturn('');

        $this->viewModel = new PdpViewModel(
            $this->configMock,
            $this->registryMock,
            $idProviderStub,
            $storeManagerMock,
            $this->serializerMock,
            $loggerMock
        );

        $this->configMock->expects($this->once())
            ->method('shouldRender')
            ->willReturn(true);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($product);

        $product->expects($this->once())
            ->method('getId')
            ->willReturn(10);

        $product->expects($this->once())
            ->method('getFinalPrice')
            ->willReturn(10.0);

        $storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willThrowException(new NoSuchEntityException(__('Store not found')));

        $loggerMock->expects($this->once())
            ->method('error')
            ->with('Store not found');

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($this->callback(static function (array $data): bool {
                return $data['currency'] === null;
            }))
            ->willReturn('serialized-json');

        $this->assertSame('serialized-json', $this->viewModel->getProductPageData());
    }

    /**
     * @return Product|MockObject
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getFinalPrice', 'getPrice'])
            ->getMock();
    }

    /**
     * @return Store|MockObject
     */
    private function createStoreMock()
    {
        return $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCurrentCurrencyCode'])
            ->getMock();
    }
}
