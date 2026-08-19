<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\StandardOptionsProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class StandardOptionsProviderTest extends TestCase
{
    private SpecificationBuilderInterface $specificationBuilder;

    private ContextManagerInterface $contextManager;

    private ParentRelationsContext $parentRelationsContext;

    private ProductRepositoryInterface $productRepository;

    private StandardOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $objectManager->get(SpecificationBuilderInterface::class);
        $this->contextManager = $objectManager->get(ContextManagerInterface::class);
        $this->parentRelationsContext = $objectManager->get(ParentRelationsContext::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->provider = $objectManager->create(StandardOptionsProvider::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testStandaloneAndParentAwareRows(): void
    {
        require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_products_rollback.php';
        require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_products.php';

        try {
            $specification = $this->specificationBuilder->build([]);
            $this->contextManager->setContextFromSpecification($specification);

            $simple = $this->productRepository->get('athoscommerce_configurable_test_simple_10', false, null, true);
            $this->parentRelationsContext->buildContext([(int)$simple->getId()], $specification);

            $result = $this->provider->getData([
                [
                    'entity_id' => (int)$simple->getId(),
                    'product_model' => $simple,
                    Constant::IS_STANDALONE_PRODUCT_KEY => true,
                ],
                [
                    'entity_id' => (int)$simple->getId(),
                    'product_model' => $simple,
                    Constant::IS_STANDALONE_PRODUCT_KEY => false,
                ],
            ], $specification);

            $this->assertSame([], $result[0][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS]);
            $this->assertSame(
                [
                    'test_configurable_first' => [
                        'label' => 'Test Configurable First',
                        'value' => 'First Option 1',
                    ],
                ],
                $result[1][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS]
            );
        } finally {
            $this->parentRelationsContext->reset();
            $this->contextManager->resetContext();
            $this->provider->reset();
            require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_products_rollback.php';
        }
    }
}
