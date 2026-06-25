<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Service\Tracking;

use AthosCommerce\Feed\Service\Tracking\CartItemIdentityResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartItemIdentityResolverTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var CartItemIdentityResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        $this->resolver = new CartItemIdentityResolver(
            $this->productRepositoryMock,
            $this->serializerMock
        );
    }

    public function testGetUidReturnsOwnProductIdForStandaloneSimple(): void
    {
        $product = $this->createProductMock(17, 'simple-sku');
        $cartItem = $this->createStandaloneCartItemMock($product, 17, 'simple-sku');

        $this->assertSame('17', $this->resolver->getUid($cartItem));
    }

    public function testGetParentIdReturnsOwnIdForStandaloneSimple(): void
    {
        $product = $this->createProductMock(17, 'simple-sku');
        $cartItem = $this->createStandaloneCartItemMock($product, 17, 'simple-sku');

        $this->assertSame('17', $this->resolver->getParentId($cartItem));
    }

    public function testGetSkuReturnsOwnSkuForStandaloneSimple(): void
    {
        $product = $this->createProductMock(17, 'simple-sku');
        $cartItem = $this->createStandaloneCartItemMock($product, 17, 'simple-sku');

        $this->assertSame('simple-sku', $this->resolver->getSku($cartItem));
    }

    public function testConfigurableParentAddUsesSelectedSimpleForUid(): void
    {
        $parentProduct = $this->createProductMock(12, 'configurable-parent');
        $childProduct = $this->createProductMock(9, 'child-sku');

        $simpleProductOption = $this->createMock(Option::class);
        $simpleProductOption->method('getProduct')->willReturn($childProduct);

        $cartItem = $this->createCartItemMock();
        $cartItem->method('getProduct')->willReturn($parentProduct);
        $cartItem->method('getProductId')->willReturn(12);
        $cartItem->method('getSku')->willReturn('configurable-parent');
        $cartItem->method('getOptionByCode')->willReturnCallback(function (string $code) use ($simpleProductOption) {
            $map = array(
                'simple_product' => $simpleProductOption,
                'attributes' => $this->createMock(Option::class),
                'super_attribute' => null,
                'info_buyRequest' => null,
            );

            return $map[$code] ?? null;
        });

        $this->assertSame('9', $this->resolver->getUid($cartItem));
    }

    public function testConfigurableParentAddUsesParentIdAsParentId(): void
    {
        $parentProduct = $this->createProductMock(12, 'configurable-parent');
        $childProduct = $this->createProductMock(9, 'child-sku');

        $simpleProductOption = $this->createMock(Option::class);
        $simpleProductOption->method('getProduct')->willReturn($childProduct);

        $cartItem = $this->createCartItemMock();
        $cartItem->method('getProduct')->willReturn($parentProduct);
        $cartItem->method('getProductId')->willReturn(12);
        $cartItem->method('getSku')->willReturn('configurable-parent');
        $cartItem->method('getOptionByCode')->willReturnCallback(function (string $code) use ($simpleProductOption) {
            $map = array(
                'simple_product' => $simpleProductOption,
                'attributes' => $this->createMock(Option::class),
                'super_attribute' => null,
                'info_buyRequest' => null,
            );

            return $map[$code] ?? null;
        });

        $this->assertSame('12', $this->resolver->getParentId($cartItem));
    }

    public function testConfigurableParentAddUsesChildSku(): void
    {
        $parentProduct = $this->createProductMock(12, 'configurable-parent');
        $childProduct = $this->createProductMock(9, 'child-sku');

        $simpleProductOption = $this->createMock(Option::class);
        $simpleProductOption->method('getProduct')->willReturn($childProduct);

        $cartItem = $this->createCartItemMock();
        $cartItem->method('getProduct')->willReturn($parentProduct);
        $cartItem->method('getProductId')->willReturn(12);
        $cartItem->method('getSku')->willReturn('configurable-parent');
        $cartItem->method('getOptionByCode')->willReturnCallback(function (string $code) use ($simpleProductOption) {
            $map = array(
                'simple_product' => $simpleProductOption,
                'attributes' => $this->createMock(Option::class),
                'super_attribute' => null,
                'info_buyRequest' => null,
            );

            return $map[$code] ?? null;
        });

        $this->assertSame('child-sku', $this->resolver->getSku($cartItem));
    }

    public function testVisibleConfigurableChildDirectAddRemainsStandalone(): void
    {
        $childProduct = $this->createProductMock(11, 'visible-child-sku');
        $cartItem = $this->createStandaloneCartItemMock($childProduct, 11, 'visible-child-sku');

        $this->assertSame('11', $this->resolver->getUid($cartItem));
        $this->assertSame('11', $this->resolver->getParentId($cartItem));
        $this->assertSame('visible-child-sku', $this->resolver->getSku($cartItem));
    }

    public function testGroupedParentAddUsesStandaloneChildAsUid(): void
    {
        $childProduct = $this->createProductMock(17, 'grouped-child-sku');
        $buyRequestOption = $this->createMock(Option::class);
        $buyRequestOption->method('getValue')->willReturn('serialized-value');

        $cartItem = $this->createCartItemMock();
        $cartItem->method('getProduct')->willReturn($childProduct);
        $cartItem->method('getProductId')->willReturn(17);
        $cartItem->method('getSku')->willReturn('grouped-child-sku');
        $cartItem->method('getOptionByCode')->willReturnCallback(function (string $code) use ($buyRequestOption) {
            if ($code === 'info_buyRequest') {
                return $buyRequestOption;
            }

            return null;
        });

        $this->serializerMock->expects($this->atLeastOnce())
            ->method('unserialize')
            ->with('serialized-value')
            ->willReturn(array(
                'super_product_config' => array(
                    'product_type' => 'grouped',
                    'product_id' => '19',
                ),
            ));

        $this->assertSame('17', $this->resolver->getUid($cartItem));
    }

    public function testGroupedParentAddUsesGroupedParentId(): void
    {
        $childProduct = $this->createProductMock(17, 'grouped-child-sku');
        $buyRequestOption = $this->createMock(Option::class);
        $buyRequestOption->method('getValue')->willReturn('serialized-value');

        $cartItem = $this->createCartItemMock();
        $cartItem->method('getProduct')->willReturn($childProduct);
        $cartItem->method('getProductId')->willReturn(17);
        $cartItem->method('getSku')->willReturn('grouped-child-sku');
        $cartItem->method('getOptionByCode')->willReturnCallback(function (string $code) use ($buyRequestOption) {
            if ($code === 'info_buyRequest') {
                return $buyRequestOption;
            }

            return null;
        });

        $this->serializerMock->expects($this->atLeastOnce())
            ->method('unserialize')
            ->with('serialized-value')
            ->willReturn(array(
                'super_product_config' => array(
                    'product_type' => 'grouped',
                    'product_id' => '19',
                ),
            ));

        $this->assertSame('19', $this->resolver->getParentId($cartItem));
    }

    public function testGroupedVisibleAssociatedSimpleDirectAddRemainsStandalone(): void
    {
        $childProduct = $this->createProductMock(18, 'associated-simple-sku');
        $cartItem = $this->createStandaloneCartItemMock($childProduct, 18, 'associated-simple-sku');

        $this->assertSame('18', $this->resolver->getUid($cartItem));
        $this->assertSame('18', $this->resolver->getParentId($cartItem));
        $this->assertSame('associated-simple-sku', $this->resolver->getSku($cartItem));
    }

    public function testGetResolvedProductFallsBackToSelectedConfigurableOptionFromBuyRequest(): void
    {
        $parentProduct = $this->createProductMock(12, 'configurable-parent');
        $childProduct = $this->createProductMock(9, 'child-sku');

        $buyRequestOption = $this->createMock(Option::class);
        $buyRequestOption->method('getValue')->willReturn('serialized-value');

        $cartItem = $this->createCartItemMock();
        $cartItem->method('getProduct')->willReturn($parentProduct);
        $cartItem->method('getProductId')->willReturn(12);
        $cartItem->method('getSku')->willReturn('configurable-parent');
        $cartItem->method('getOptionByCode')->willReturnCallback(function (string $code) use ($buyRequestOption) {
            if ($code === 'info_buyRequest') {
                return $buyRequestOption;
            }

            return null;
        });

        $this->serializerMock->expects($this->atLeastOnce())
            ->method('unserialize')
            ->with('serialized-value')
            ->willReturn(array(
                'selected_configurable_option' => '9',
            ));

        $this->productRepositoryMock->expects($this->atLeastOnce())
            ->method('getById')
            ->with(9)
            ->willReturn($childProduct);

        $this->assertSame('9', $this->resolver->getUid($cartItem));
        $this->assertSame('child-sku', $this->resolver->getSku($cartItem));
    }

    private function createCartItemMock(): Item
    {
        return $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getProduct', 'getSku', 'getOptionByCode'))
            ->addMethods(array('getProductId'))
            ->getMock();
    }

    private function createStandaloneCartItemMock(Product $product, int $productId, string $sku): Item
    {
        $cartItem = $this->createCartItemMock();
        $cartItem->method('getProduct')->willReturn($product);
        $cartItem->method('getProductId')->willReturn($productId);
        $cartItem->method('getSku')->willReturn($sku);
        $cartItem->method('getOptionByCode')->willReturn(null);

        return $cartItem;
    }

    private function createProductMock(int $id, string $sku): Product
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getId', 'getSku'))
            ->getMock();

        $product->method('getId')->willReturn($id);
        $product->method('getSku')->willReturn($sku);

        return $product;
    }
}
