<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\ViewModel;

use AthosCommerce\Feed\ViewModel\CartViewModel;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CartViewModelTest extends TestCase
{
    /**
     * @var CartViewModel
     */
    private $viewModel;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /*
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->viewModel = $objectManager->get(CartViewModel::class);
        $this->checkoutSession = $objectManager->get(CheckoutSession::class);
        $this->cartRepository = $objectManager->get(CartRepositoryInterface::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->serializer = $objectManager->get(SerializerInterface::class);

        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testReturnsEmptyStringWhenRenderIsDisabled(): void
    {
        $this->assertSame('', $this->viewModel->getCartPageData());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     * @magentoConfigFixture default_store athoscommerce/tracking/script_src https://cdn.dev-athoscommerce.com/tracking.js
     */
    public function testReturnsEmptyStringWhenQuoteIsMissing(): void
    {
        $quote = Bootstrap::getObjectManager()->create(Quote::class);
        $this->checkoutSession->replaceQuote($quote);

        $this->assertSame('', $this->viewModel->getCartPageData());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store athoscommerce/general/site_id site-athos-demo
     * @magentoConfigFixture default_store athoscommerce/tracking/script_src https://cdn.dev-athoscommerce.com/tracking.js
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products.php
     *
     * @dataProvider quoteScenarioProvider
     */
    public function testReturnsSerializedCartDataWithExpectedParentId(
        callable $quoteBuilder,
        string   $expectedSku,
        ?string  $expectedParentSku
    ): void
    {
        $quote = $this->createQuote();
        $quoteBuilder($quote, $this->productRepository);
        $this->cartRepository->save($quote);
        $this->checkoutSession->replaceQuote($quote);

        $result = $this->viewModel->getCartPageData();
        $this->assertNotSame('', $result);

        $items = $this->serializer->unserialize($result);
        $this->assertIsArray($items);
        $this->assertCount(1, $items);

        $item = $items[0];
        $this->assertSame($expectedSku, $item['sku']);
        $this->assertArrayHasKey('parentId', $item);

        if ($expectedParentSku === null) {
            $this->assertNull($item['parentId']);
            return;
        }

        $parentProduct = $this->productRepository->get($expectedParentSku);
        $this->assertSame((string)$parentProduct->getId(), $item['parentId']);
    }

    /**
     * @return array[]
     */
    public static function quoteScenarioProvider(): array
    {
        return [
            'simple product' => [
                'quoteBuilder' => function (Quote $quote, ProductRepositoryInterface $productRepository): void {
                    $product = $productRepository->get('athoscommerce_simple_1');
                    $quote->addProduct($product, 2);
                    $quote->collectTotals();
                },
                'expectedSku' => 'athoscommerce_simple_1',
                'expectedParentSku' => null,
            ],
        ];
    }

    /**
     * @return Quote
     */
    private function createQuote(): Quote
    {
        $objectManager = ObjectManager::getInstance();

        /** @var Quote $quote */
        $quote = $objectManager->create(Quote::class);
        $quote->setStoreId(1);
        $quote->setIsActive(true);
        $quote->setCheckoutMethod('guest');
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerEmail('cartviewmodel@example.com');
        $quote->setCustomerFirstname('Cart');
        $quote->setCustomerLastname('Test');

        return $quote;
    }
}
