<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Service\Tracking;

use AthosCommerce\Feed\Service\Tracking\IdProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IdProviderTest extends TestCase
{
    /**
     * @var LinkManagementInterface|MockObject
     */
    private $linkManagementMock;

    /**
     * @var Configurable|MockObject
     */
    private $configurableTypeMock;

    /**
     * @var Grouped|MockObject
     */
    private $groupedTypeMock;

    /**
     * @var IdProvider
     */
    private $idProvider;

    protected function setUp(): void
    {
        $this->linkManagementMock = $this->createMock(LinkManagementInterface::class);
        $this->configurableTypeMock = $this->createMock(Configurable::class);
        $this->groupedTypeMock = $this->createMock(Grouped::class);

        $this->idProvider = new IdProvider(
            $this->linkManagementMock,
            $this->configurableTypeMock,
            $this->groupedTypeMock
        );
    }

    public function testGetItemIdReturnsOwnIdForSimpleProduct(): void
    {
        $product = $this->createProductInterfaceMock(5, 'simple-sku', 'simple');

        $this->assertSame('5', $this->idProvider->getItemId($product));
    }

    public function testGetItemSkuReturnsOwnSkuForSimpleProduct(): void
    {
        $product = $this->createProductInterfaceMock(5, 'simple-sku', 'simple');

        $this->assertSame('simple-sku', $this->idProvider->getItemSku($product));
    }

    public function testGetItemParentIdReturnsOwnIdForStandaloneSimpleWithoutParents(): void
    {
        $product = $this->createProductInterfaceMock(5, 'simple-sku', 'simple');

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(5)
            ->willReturn(array());

        $this->groupedTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(5)
            ->willReturn(array());

        $this->assertSame('5', $this->idProvider->getItemParentId($product));
    }

    public function testGetItemParentIdReturnsConfigurableParentIdForSimpleChild(): void
    {
        $product = $this->createProductInterfaceMock(11, 'child-sku', 'simple');

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(11)
            ->willReturn(array(12));

        $this->groupedTypeMock->expects($this->never())
            ->method('getParentIdsByChild');

        $this->assertSame('12', $this->idProvider->getItemParentId($product));
    }

    public function testGetItemParentIdReturnsGroupedParentIdWhenNoConfigurableParentExists(): void
    {
        $product = $this->createProductInterfaceMock(18, 'grouped-child-sku', 'simple');

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(18)
            ->willReturn(array());

        $this->groupedTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(18)
            ->willReturn(array(19));

        $this->assertSame('19', $this->idProvider->getItemParentId($product));
    }

    public function testGetItemParentIdReturnsOwnIdForConfigurableProduct(): void
    {
        $product = $this->createProductInterfaceMock(12, 'configurable-parent', Configurable::TYPE_CODE);

        $this->assertSame('12', $this->idProvider->getItemParentId($product));
    }

    public function testGetItemParentIdReturnsOwnIdForGroupedProduct(): void
    {
        $product = $this->createProductInterfaceMock(19, 'grouped-parent', Grouped::TYPE_CODE);

        $this->assertSame('19', $this->idProvider->getItemParentId($product));
    }

    public function testGetItemIdReturnsFirstAvailableConfigurableChildId(): void
    {
        $parentProduct = $this->createProductInterfaceMock(12, 'configurable-parent', Configurable::TYPE_CODE);
        $availableChild = $this->createAvailableProductMock(9, 'child-sku', 'simple', true);

        $this->linkManagementMock->expects($this->once())
            ->method('getChildren')
            ->with('configurable-parent')
            ->willReturn(array($availableChild));

        $this->assertSame('9', $this->idProvider->getItemId($parentProduct));
    }

    public function testGetItemSkuReturnsFirstAvailableConfigurableChildSku(): void
    {
        $parentProduct = $this->createProductInterfaceMock(12, 'configurable-parent', Configurable::TYPE_CODE);
        $availableChild = $this->createAvailableProductMock(9, 'child-sku', 'simple', true);

        $this->linkManagementMock->expects($this->once())
            ->method('getChildren')
            ->with('configurable-parent')
            ->willReturn(array($availableChild));

        $this->assertSame('child-sku', $this->idProvider->getItemSku($parentProduct));
    }

    public function testGetItemIdFallsBackToOwnIdWhenNoAvailableConfigurableChildExists(): void
    {
        $parentProduct = $this->createProductInterfaceMock(12, 'configurable-parent', Configurable::TYPE_CODE);
        $unavailableChild = $this->createAvailableProductMock(9, 'child-sku', 'simple', false);

        $this->linkManagementMock->expects($this->once())
            ->method('getChildren')
            ->with('configurable-parent')
            ->willReturn(array($unavailableChild));

        $this->assertSame('12', $this->idProvider->getItemId($parentProduct));
    }

    public function testGetItemSkuFallsBackToOwnSkuWhenNoAvailableConfigurableChildExists(): void
    {
        $parentProduct = $this->createProductInterfaceMock(12, 'configurable-parent', Configurable::TYPE_CODE);
        $unavailableChild = $this->createAvailableProductMock(9, 'child-sku', 'simple', false);

        $this->linkManagementMock->expects($this->once())
            ->method('getChildren')
            ->with('configurable-parent')
            ->willReturn(array($unavailableChild));

        $this->assertSame('configurable-parent', $this->idProvider->getItemSku($parentProduct));
    }

    public function testGetItemIdReturnsFirstAvailableGroupedChildId(): void
    {
        $availableGroupedChild = $this->createAvailableProductMock(17, 'grouped-child-sku', 'simple', true);
        $typeInstanceMock = $this->getGroupedTypeInstanceMock(array($availableGroupedChild));
        $parentProduct = $this->createCatalogProductMock(19, 'grouped-parent', Grouped::TYPE_CODE, $typeInstanceMock);

        $this->linkManagementMock->expects($this->never())
            ->method('getChildren');

        $this->assertSame('17', $this->idProvider->getItemId($parentProduct));
    }

    public function testGetItemSkuReturnsFirstAvailableGroupedChildSku(): void
    {
        $availableGroupedChild = $this->createAvailableProductMock(17, 'grouped-child-sku', 'simple', true);
        $typeInstanceMock = $this->getGroupedTypeInstanceMock(array($availableGroupedChild));
        $parentProduct = $this->createCatalogProductMock(19, 'grouped-parent', Grouped::TYPE_CODE, $typeInstanceMock);

        $this->linkManagementMock->expects($this->never())
            ->method('getChildren');

        $this->assertSame('grouped-child-sku', $this->idProvider->getItemSku($parentProduct));
    }

    public function testGetItemIdReturnsOwnIdWhenGroupedProductHasNoAvailableAssociatedProducts(): void
    {
        $unavailableGroupedChild = $this->createAvailableProductMock(17, 'grouped-child-sku', 'simple', false);
        $typeInstanceMock = $this->getGroupedTypeInstanceMock(array($unavailableGroupedChild));
        $parentProduct = $this->createCatalogProductMock(19, 'grouped-parent', Grouped::TYPE_CODE, $typeInstanceMock);

        $this->linkManagementMock->expects($this->never())
            ->method('getChildren');

        $this->assertSame('19', $this->idProvider->getItemId($parentProduct));
    }

    public function testGetItemSkuReturnsOwnSkuWhenGroupedProductHasNoAvailableAssociatedProducts(): void
    {
        $unavailableGroupedChild = $this->createAvailableProductMock(17, 'grouped-child-sku', 'simple', false);
        $typeInstanceMock = $this->getGroupedTypeInstanceMock(array($unavailableGroupedChild));
        $parentProduct = $this->createCatalogProductMock(19, 'grouped-parent', Grouped::TYPE_CODE, $typeInstanceMock);

        $this->linkManagementMock->expects($this->never())
            ->method('getChildren');

        $this->assertSame('grouped-parent', $this->idProvider->getItemSku($parentProduct));
    }

    public function testGetItemIdReturnsOwnIdWhenGroupedProductHasNoAssociatedProducts(): void
    {
        $typeInstanceMock = $this->getGroupedTypeInstanceMock(array());
        $parentProduct = $this->createCatalogProductMock(19, 'grouped-parent', Grouped::TYPE_CODE, $typeInstanceMock);

        $this->linkManagementMock->expects($this->never())
            ->method('getChildren');

        $this->assertSame('19', $this->idProvider->getItemId($parentProduct));
    }

    public function testGetItemSkuReturnsOwnSkuWhenGroupedProductHasNoAssociatedProducts(): void
    {
        $typeInstanceMock = $this->getGroupedTypeInstanceMock(array());
        $parentProduct = $this->createCatalogProductMock(19, 'grouped-parent', Grouped::TYPE_CODE, $typeInstanceMock);

        $this->linkManagementMock->expects($this->never())
            ->method('getChildren');

        $this->assertSame('grouped-parent', $this->idProvider->getItemSku($parentProduct));
    }

    private function createProductInterfaceMock(
        int $id,
        string $sku,
        string $typeId
    ): ProductInterface {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn($id);
        $product->method('getSku')->willReturn($sku);
        $product->method('getTypeId')->willReturn($typeId);

        return $product;
    }

    private function createCatalogProductMock(
        int $id,
        string $sku,
        string $typeId,
        MockObject $typeInstance
    ): Product {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getId', 'getSku', 'getTypeId', 'getTypeInstance'))
            ->getMock();

        $product->method('getId')->willReturn($id);
        $product->method('getSku')->willReturn($sku);
        $product->method('getTypeId')->willReturn($typeId);
        $product->method('getTypeInstance')->willReturn($typeInstance);

        return $product;
    }

    private function createAvailableProductMock(
        int $id,
        string $sku,
        string $typeId,
        bool $isAvailable
    ): ProductInterface {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getId', 'getSku', 'getTypeId', 'isAvailable'))
            ->getMock();

        $product->method('getId')->willReturn($id);
        $product->method('getSku')->willReturn($sku);
        $product->method('getTypeId')->willReturn($typeId);
        $product->method('isAvailable')->willReturn($isAvailable);

        return $product;
    }

    private function getGroupedTypeInstanceMock(array $associatedProducts): MockObject
    {
        $typeInstance = $this->getMockBuilder(\stdClass::class)
            ->addMethods(array('getAssociatedProducts'))
            ->getMock();

        $typeInstance->method('getAssociatedProducts')->willReturn($associatedProducts);

        return $typeInstance;
    }
}
