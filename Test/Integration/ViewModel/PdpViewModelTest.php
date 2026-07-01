<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\ViewModel;

use AthosCommerce\Feed\ViewModel\PdpViewModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class PdpViewModelTest extends TestCase
{
    /**
     * @var PdpViewModel
     */
    private $viewModel;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->viewModel = $objectManager->get(PdpViewModel::class);
        $this->registry = $objectManager->get(Registry::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->serializer = $objectManager->get(SerializerInterface::class);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->unregisterCurrentProduct();
        parent::tearDown();
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testReturnsEmptyStringWhenCurrentProductIsMissing(): void
    {
        $this->unregisterCurrentProduct();

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     * @magentoConfigFixture default_store athoscommerce/tracking/script_src https://cdn.dev-athoscommerce.com/tracking.js
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     */
    public function testReturnsSerializedProductPageDataForSimpleProduct(): void
    {
        $product = $this->productRepository->get('athoscommerce_simple_1');

        $this->registerCurrentProduct($product);
        $data = $this->getDecodedProductPageData();

        $this->assertSame((string) $product->getId(), $data['uid']);
        $this->assertSame((string) $product->getSku(), $data['sku']);
        $this->assertArrayHasKey('parentId', $data);
        $this->assertSame((string) $product->getId(), $data['parentId']);
        $this->assertArrayHasKey('price', $data);
        $this->assertEquals((float) $product->getFinalPrice(), (float) $data['price']);
        $this->assertArrayHasKey('currency', $data);
        $this->assertIsString($data['currency']);
        $this->assertNotSame('', $data['currency']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     */
    public function testReturnsEmptyStringWhenRenderIsDisabled(): void
    {
        $product = $this->productRepository->get('athoscommerce_simple_1');

        $this->registerCurrentProduct($product);

        $this->assertSame('', $this->viewModel->getProductPageData());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     */
    public function testReturnsDefaultTrackingScriptViaConfigAndStillRenders(): void
    {
        $product = $this->productRepository->get('athoscommerce_simple_1');

        $this->registerCurrentProduct($product);
        $data = $this->getDecodedProductPageData();

        $this->assertSame((string) $product->getId(), $data['uid']);
        $this->assertSame((string) $product->getId(), $data['parentId']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/grouped/grouped_products.php
     */
    public function testReturnsGroupedParentIdForChildProduct(): void
    {
        $childSku = 'athoscommerce_grouped_test_simple_1001';
        $parentSku = 'athoscommerce_grouped_test_grouped_2';

        $childProduct = $this->productRepository->get($childSku);
        $parentProduct = $this->productRepository->get($parentSku);

        $this->registerCurrentProduct($childProduct);
        $data = $this->getDecodedProductPageData();

        $this->assertSame((string) $childProduct->getId(), $data['uid']);
        $this->assertSame($childSku, $data['sku']);
        $this->assertSame((string) $parentProduct->getId(), $data['parentId']);
    }

    /**
     * @param ProductInterface $product
     */
    private function registerCurrentProduct(ProductInterface $product): void
    {
        $this->unregisterCurrentProduct();
        $this->registry->register('current_product', $product);
    }

    private function unregisterCurrentProduct(): void
    {
        if ($this->registry->registry('current_product')) {
            $this->registry->unregister('current_product');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getDecodedProductPageData(): array
    {
        $result = $this->viewModel->getProductPageData();

        $this->assertNotSame('', $result);

        $data = $this->serializer->unserialize($result);
        $this->assertIsArray($data);

        return $data;
    }
}
