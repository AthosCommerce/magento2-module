<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Integration\Model\Feed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\DataProvider\JsonConfigProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JsonConfigProviderTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var GetProducts
     */
    private $getProducts;
    /**
     * @var JsonConfigProvider
     */
    private $jsonConfigProvider;
    /**
     * @var Json
     */
    private $json;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->jsonConfigProvider = $this->objectManager->get(JsonConfigProvider::class);
        $this->json = $this->objectManager->get(Json::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_products.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_product_two_swatches_attributes.php
     *
     * @throws \Exception
     */
    public function testGetData() : void
    {
        $specification = $this->specificationBuilder->build(['includeJSONConfig' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->jsonConfigProvider->getData($products, $specification);
        $config = [
            'athoscommerce_configurable_test_configurable' => [
                'swatches_not_empty' => false,
            ],
            'athoscommerce_configurable_test_configurable_2_attributes' => [
                'swatches_not_empty' => false,
            ],
            'configurable' => [
                'swatches_not_empty' => true,
            ],
        ];
        foreach ($data as $item) {
            /** @var Product $model */
            $model = $item['product_model'] ?? null;
            if (is_null($model)) {
                $this->fail('product_model is not exist');
            }

            if ($model->getTypeId() === 'configurable') {
                $sku = $model->getSku();
                $id = $model->getId();
                $this->assertArrayHasKey('json_config', $item);
                $this->assertArrayHasKey('swatch_json_config', $item);
                $jsonConfig = $this->json->unserialize($item['json_config']);
                $this->assertEquals($id, $jsonConfig['productId']);
                $swatchRequired = $config[$sku]['swatches_not_empty'];
                if ($swatchRequired) {
                    $swatchConfig = $this->json->unserialize($item['swatch_json_config']);
                    $this->assertNotEmpty($swatchConfig);
                }
            } else {
                $this->assertArrayNotHasKey('json_config', $item);
                $this->assertArrayNotHasKey('swatch_json_config', $item);
            }
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/configurable_products.php
     *
     * @throws \Exception
     */
    public function testReset() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->jsonConfigProvider->getData($products, $specification);
        $this->jsonConfigProvider->reset();
        $this->assertTrue(true);
    }
}
