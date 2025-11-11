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

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\ContextManagerInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\CategoriesProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoriesProviderTest extends TestCase
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
     * @var CategoriesProvider
     */
    private $categoriesProvider;
    /**
     * @var ContextManagerInterface
     */
    private $contextManager;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->categoriesProvider = $this->objectManager->get(CategoriesProvider::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        $this->contextManager = $this->objectManager->get(ContextManagerInterface::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_categories.php
     *
     * @throws \Exception
     */
    public function testGetData() : void
    {
        $specification = $this->specificationBuilder->build(['includeUrlHierarchy' => true, 'includeMenuCategories' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->categoriesProvider->getData($products, $specification);
        $categoriesBySku = [
            'athoscommerce_simple_1' => [1000, 1001, 1002, 1012],
            'athoscommerce_simple_2' => [1002, 1012]
        ];
        $requiredFields = ['categories', 'category_ids', 'category_hierarchy', 'menu_hierarchy', 'url_hierarchy'];
        $this->assertCategories($data, $requiredFields, $categoriesBySku);
        $this->categoriesProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_categories.php
     *
     * @throws \Exception
     */
    public function testGetDataWithoutMenuCategories() : void
    {
        $specification = $this->specificationBuilder->build(['includeUrlHierarchy' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->categoriesProvider->getData($products, $specification);
        $requiredFields = ['categories', 'category_ids', 'category_hierarchy', 'url_hierarchy'];
        $ignoredFields = ['menu_hierarchy'];
        $this->assertCategories($data, $requiredFields, [], $ignoredFields);
        $this->categoriesProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_categories.php
     *
     * @throws \Exception
     */
    public function testGetDataWithoutUrlHierarchy() : void
    {
        $specification = $this->specificationBuilder->build(['includeMenuCategories' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->categoriesProvider->getData($products, $specification);
        $requiredFields = ['categories', 'category_ids', 'category_hierarchy', 'menu_hierarchy'];
        $ignoredFields = ['url_hierarchy'];
        $this->assertCategories($data, $requiredFields, [], $ignoredFields);
        $this->categoriesProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_categories.php
     *
     * @throws \Exception
     */
    public function testGetDataIgnoringAllFields() : void
    {
        $ignoredFields = ['categories', 'category_ids', 'category_hierarchy', 'menu_hierarchy', 'url_hierarchy'];
        $specification = $this->specificationBuilder->build(
            ['includeUrlHierarchy' => true, 'includeMenuCategories' => true, 'ignoreFields' => $ignoredFields]
        );
        $products = $this->getProducts->get($specification);
        $data = $this->categoriesProvider->getData($products, $specification);
        $ignoredFields = ['categories', 'category_ids', 'category_hierarchy', 'menu_hierarchy', 'url_hierarchy'];
        $this->assertCategories($data, [], [], $ignoredFields);
        $this->categoriesProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_categories_in_different_websites.php
     *
     * @throws \Exception
     */
    public function testGetDataWithMultistore() : void
    {
        $specification = $this->specificationBuilder->build(['includeUrlHierarchy' => true, 'includeMenuCategories' => true]);
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->categoriesProvider->getData($products, $specification);
        $categoriesBySku = [
            'athoscommerce_simple_1' => [1000, 1001, 1002, 1012, 2000, 2001],
            'athoscommerce_simple_2' => [1002, 1012, 2000, 2001]
        ];
        $requiredFields = ['categories', 'category_ids', 'category_hierarchy', 'menu_hierarchy', 'url_hierarchy'];
        $this->assertCategories($data, $requiredFields, $categoriesBySku);
        $this->categoriesProvider->reset();
        $this->contextManager->resetContext();
        $specification = $this->specificationBuilder->build(
            ['includeUrlHierarchy' => true, 'includeMenuCategories' => true, 'store' => 'test_store_1']
        );
        $this->contextManager->setContextFromSpecification($specification);
        $products = $this->getProducts->get($specification);
        $data = $this->categoriesProvider->getData($products, $specification);
        $requiredFields = ['categories', 'category_ids', 'category_hierarchy', 'menu_hierarchy', 'url_hierarchy'];
        $this->assertCategories($data, $requiredFields, $categoriesBySku);
        $this->categoriesProvider->reset();
        $this->contextManager->resetContext();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_categories.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/categories/categories_store_specific_data.php
     *
     * @throws \Exception
     */
    public function testGetDataWithStoreSpecificCategoryChanges() : void
    {
        $specification = $this->specificationBuilder->build(['includeUrlHierarchy' => true, 'includeMenuCategories' => true]);
        $products = $this->getProducts->get($specification);
        $data = $this->categoriesProvider->getData($products, $specification);
        $categoriesBySku = [
            'athoscommerce_simple_1' => [1000, 1001, 1002, 1012],
            'athoscommerce_simple_2' => [1002, 1012]
        ];
        $requiredFields = ['categories', 'category_ids', 'category_hierarchy', 'menu_hierarchy', 'url_hierarchy'];
        $this->assertCategories($data, $requiredFields, $categoriesBySku);
        foreach ($data as $item) {
            $categories = $item['categories'];
            foreach ($categories as $categoryName) {
                $this->assertTrue(strpos($categoryName, 'Store default') !== false);
            }
        }
        $this->categoriesProvider->reset();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple_products_with_categories.php
     *
     * @throws \Exception
     */
    public function testReset() : void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->categoriesProvider->getData($products, $specification);
        $this->categoriesProvider->reset();
        $this->assertTrue(true);
    }

    /**
     * @param array $items
     * @param array $requiredFields
     * @param array $skuIdMap
     * @param array $notIncludedFields
     */
    private function assertCategories(
        array $items,
        array $requiredFields = [],
        array $skuIdMap = [],
        array $notIncludedFields = []
    ) : void {
        foreach ($items as $item) {
            foreach ($requiredFields as $field) {
                $this->assertTrue(isset($item[$field]) && !empty($item[$field]));
            }

            foreach ($notIncludedFields as $field) {
                $this->assertTrue(!array_key_exists($field, $item));
            }

            $sku = $item['product_model']->getSku();
            $categories = $skuIdMap[$sku] ?? null;
            if ($categories) {
                $categoryIds = $item['category_ids'] ?? [];
                $check = empty(array_diff($categories, $categoryIds)) && empty(array_diff($categoryIds, $categories));
                $this->assertTrue($check);
            }
        }
    }
}
