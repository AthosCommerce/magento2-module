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
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/simple_products_catalogrule.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable/configurable_products.php
     *
     * @throws Exception
     */
    public function testStandaloneAndParentAwareRows(): void
    {
        try {
            $specification = $this->specificationBuilder->build([]);
            $this->contextManager->setContextFromSpecification($specification);

            $simple = $this->productRepository->get('athoscommerce_configurable_test_simple_10', false, null, true);
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
            ];

            $result = $this->provider->getData([$standaloneRow, $parentAwareRow], $specification);

            $this->assertArrayHasKey(StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS, $result[0]);
            $this->assertSame([], $result[0][StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS]);
            $this->assertArrayHasKey(StandardOptionsProvider::FIELD_KEY_STANDARD_OPTIONS, $result[1], print_r($result[1], true));
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
        }
    }
}
