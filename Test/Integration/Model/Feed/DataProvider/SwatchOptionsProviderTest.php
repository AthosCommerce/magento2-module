<?php

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\SwatchOptionsProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class SwatchOptionsProviderTest extends TestCase
{
    private SpecificationBuilderInterface $specificationBuilder;

    private ContextManagerInterface $contextManager;

    private ParentRelationsContext $parentRelationsContext;

    private ProductRepositoryInterface $productRepository;

    private SwatchOptionsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $objectManager->get(SpecificationBuilderInterface::class);
        $this->contextManager = $objectManager->get(ContextManagerInterface::class);
        $this->parentRelationsContext = $objectManager->get(ParentRelationsContext::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->provider = $objectManager->create(SwatchOptionsProvider::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testStandaloneAndParentAwareRows(): void
    {
        try {
            require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_product_two_swatches_attributes_rollback.php';
        } catch (\Throwable $exception) {
            // ignore cleanup failures before fixture creation
        }
        require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_product_two_swatches_attributes.php';

        try {
            $specification = $this->specificationBuilder->build([
                'swatchOptionSourceFieldNames' => ['visual_swatch_attribute'],
            ]);
            $this->contextManager->setContextFromSpecification($specification);

            $simple = $this->productRepository->get('simple_option_1_option_1', false, null, true);
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

            $this->assertSame([], $result[0][SwatchOptionsProvider::FIELD_KEY]);

            $parentAwareOptions = $result[1][SwatchOptionsProvider::FIELD_KEY];
            $this->assertArrayHasKey('visual_swatch_attribute', $parentAwareOptions);
            $this->assertSame(
                'Visual swatch attribute',
                $parentAwareOptions['visual_swatch_attribute']['label']
            );
            $this->assertSame(
                (string)$simple->getAttributeText('visual_swatch_attribute'),
                $parentAwareOptions['visual_swatch_attribute']['value']
            );
            $this->assertSame(['#555555'], $parentAwareOptions['visual_swatch_attribute']['colors']);
            $this->assertArrayHasKey('id', $parentAwareOptions['visual_swatch_attribute']);
            $this->assertNull($parentAwareOptions['visual_swatch_attribute']['image']);
        } finally {
            $this->parentRelationsContext->reset();
            $this->contextManager->resetContext();
            $this->provider->reset();
            require '/var/www/html/athoscommerce/magento2-module/Test/_files/configurable/configurable_product_two_swatches_attributes_rollback.php';
        }
    }
}
