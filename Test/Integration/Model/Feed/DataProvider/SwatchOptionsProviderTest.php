<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

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
    private $specificationBuilder;

    private $contextManager;

    private $parentRelationsContext;

    private $productRepository;

    private $provider;

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
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_product_two_swatches_attributes.php
     */
    public function testStandaloneAndParentAwareRows(): void
    {

        try {
            $specification = $this->specificationBuilder->build([
                'swatchOptionSourceFieldNames' => ['visual_swatch_attribute'],
            ]);
            $this->contextManager->setContextFromSpecification($specification);

            $simple = $this->productRepository->get('simple_option_1_option_1', false, null, true);
            $parent = $this->productRepository->get('configurable', false, null, true);
            $this->parentRelationsContext->buildContext([(int)$simple->getId()], $specification);

            $standaloneRow = [
                'entity_id' => (int)$simple->getId(),
                'product_model' => $simple,
                Constant::IS_STANDALONE_PRODUCT_KEY => true,
            ];
            $parentAwareRow = [
                'entity_id' => (int)$simple->getId(),
                'product_model' => $simple,
                Constant::IS_STANDALONE_PRODUCT_KEY => false,
                Constant::PARENT_ID => (int)$parent->getId(),
                Constant::PARENT_SKU => (string)$parent->getSku(),
                Constant::RESOLVED_PARENT_ID_KEY => (int)$parent->getId(),
                Constant::RESOLVED_PARENT_SKU_KEY => (string)$parent->getSku(),
            ];

            $result = $this->provider->getData([$standaloneRow, $parentAwareRow], $specification);

            $this->assertArrayHasKey(SwatchOptionsProvider::FIELD_KEY, $result[0]);
            $this->assertSame([], $result[0][SwatchOptionsProvider::FIELD_KEY]);

            $this->assertArrayHasKey(SwatchOptionsProvider::FIELD_KEY, $result[1], print_r($result[1], true));
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
        }
    }
}
