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
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Model\Feed\DataProvider\AttributesProvider;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;

/**
 *
 * @magentoDbIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributesProviderTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var AttributesProvider
     */
    private $attributesProvider;
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var GetProducts
     */
    private $getProducts;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributesProvider = $this->objectManager->get(AttributesProvider::class);
        $this->specificationBuilder = $this->objectManager->get(SpecificationBuilderInterface::class);
        $this->getProducts = $this->objectManager->get(GetProducts::class);
        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_boolean_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_decimal_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_material_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_brand_as_select_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products_with_attributes.php
     */
    public function testGetDataMultipleAttributesWithDefaultPipeSeparator(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);

        $data = $this->attributesProvider->getData($products, $specification);
        $testAttributes = ['boolean_attribute', 'decimal_attribute'];
        foreach ($data as $item) {
            $keys = array_keys($item);
            foreach ($testAttributes as $attribute) {
                $this->assertTrue(in_array($attribute, $keys));
                $this->assertNotNull($item[$attribute] ?? null);
            }

            $athosBrandAttributeKey = 'athos_brand_select_attribute';
            $this->assertArrayHasKey(
                $athosBrandAttributeKey,
                $item,
                sprintf('Attribute "%s" is missing', $athosBrandAttributeKey)
            );

            $this->assertNotNull(
                $item[$athosBrandAttributeKey],
                sprintf('Attribute "%s" value is null', $athosBrandAttributeKey)
            );
            $expectedValue = 'Adidas';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $athosBrandAttributeKey,
                    print_r($expectedValue, true),
                    print_r($item[$athosBrandAttributeKey], true),
                )
            );

            $athosMaterialAttributeKey = 'athos_material_multi';
            $this->assertArrayHasKey(
                $athosMaterialAttributeKey,
                $item,
                sprintf('Attribute "%s" is missing', $athosMaterialAttributeKey)
            );

            $this->assertNotNull(
                $item[$athosMaterialAttributeKey],
                sprintf('Attribute "%s" value is null', $athosMaterialAttributeKey)
            );
            $expectedValue = 'Polyester|Cotton|Linen';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $athosMaterialAttributeKey,
                    print_r($expectedValue, true),
                    print_r($item[$athosMaterialAttributeKey], true),
                )
            );

            $athosSizeAttributeKey = 'athos_size_multi';
            $this->assertArrayHasKey(
                $athosSizeAttributeKey,
                $item,
                sprintf('Attribute "%s" is missing', $athosSizeAttributeKey)
            );

            $this->assertNotNull(
                $item[$athosSizeAttributeKey],
                sprintf('Attribute "%s" value is null', $athosSizeAttributeKey)
            );
            $expectedValue = 'S|M|L|5XL|3XL';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $athosSizeAttributeKey,
                    print_r($expectedValue, true),
                    print_r($item[$athosSizeAttributeKey], true),
                )
            );

            $athosColorAttributeKey = 'athos_color_multi';
            $this->assertArrayHasKey(
                $athosColorAttributeKey,
                $item,
                sprintf('Attribute "%s" is missing', $athosColorAttributeKey)
            );

            $this->assertNotNull(
                $item[$athosColorAttributeKey],
                sprintf('Attribute "%s" value is null', $athosColorAttributeKey)
            );
            $expectedValue = 'Red|Black|White|Blue|Green';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $athosColorAttributeKey,
                    print_r($expectedValue, true),
                    print_r($item[$athosColorAttributeKey], true),
                )
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_boolean_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_decimal_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_material_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_brand_as_select_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products_with_attributes.php
     */
    public function testGetDataMultipleAttributesWithUniqueSeparator(): void
    {
        $specification = $this->specificationBuilder->build(['multiValuedSeparator' => '&*&*']);
        $products = $this->getProducts->get($specification);

        $data = $this->attributesProvider->getData($products, $specification);

        foreach ($data as $item) {
            $attributeBrandKey = 'athos_brand_select_attribute';
            $this->assertArrayHasKey(
                $attributeBrandKey,
                $item,
                sprintf('Attribute "%s" is missing', $attributeBrandKey)
            );

            $this->assertNotNull(
                $item[$attributeBrandKey],
                sprintf('Attribute "%s" value is null', $attributeBrandKey)
            );
            $expectedValue = 'Adidas';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $attributeBrandKey,
                    print_r($expectedValue, true),
                    print_r($item[$attributeBrandKey], true),
                )
            );

            $attributeMaterialKey = 'athos_material_multi';
            $this->assertArrayHasKey(
                $attributeMaterialKey,
                $item,
                sprintf('Attribute "%s" is missing', $attributeMaterialKey)
            );

            $this->assertNotNull(
                $item[$attributeMaterialKey],
                sprintf('Attribute "%s" value is null', $attributeMaterialKey)
            );
            $expectedValue = 'Polyester&*&*Cotton&*&*Linen';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $attributeMaterialKey,
                    print_r($expectedValue, true),
                    print_r($item[$attributeMaterialKey], true),
                )
            );

            $attributeColorKey = 'athos_color_multi';
            $this->assertArrayHasKey(
                $attributeColorKey,
                $item,
                sprintf('Attribute "%s" is missing', $attributeColorKey)
            );

            $this->assertNotNull(
                $item[$attributeColorKey],
                sprintf('Attribute "%s" value is null', $attributeColorKey)
            );
            $expectedValue = 'Red&*Black&*White&*Blue&*Green';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $attributeColorKey,
                    print_r($expectedValue, true),
                    print_r($item[$attributeColorKey], true),
                )
            );
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_boolean_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_decimal_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_material_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_brand_as_select_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products_with_attributes.php
     */
    public function testGetDataMultipleAttributesWithCommaSeparator(): void
    {
        $specification = $this->specificationBuilder->build(['multiValuedSeparator' => ',']);
        $products = $this->getProducts->get($specification);

        $data = $this->attributesProvider->getData($products, $specification);

        foreach ($data as $item) {
            $attributeMaterialKey = 'athos_material_multi';
            $this->assertArrayHasKey(
                $attributeMaterialKey,
                $item,
                sprintf('Attribute "%s" is missing', $attributeMaterialKey)
            );

            $this->assertNotNull(
                $item[$attributeMaterialKey],
                sprintf('Attribute "%s" value is null', $attributeMaterialKey)
            );
            $expectedValue = 'Polyester,Cotton,Linen';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $attributeMaterialKey,
                    print_r($expectedValue, true),
                    print_r($item[$attributeMaterialKey], true),
                )
            );

            $attributeColorKey = 'athos_color_multi';
            $this->assertArrayHasKey(
                $attributeColorKey,
                $item,
                sprintf('Attribute "%s" is missing', $attributeColorKey)
            );

            $this->assertNotNull(
                $item[$attributeColorKey],
                sprintf('Attribute "%s" value is null', $attributeColorKey)
            );
            $expectedValue = 'Red,Black,White,Blue,Green';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $attributeColorKey,
                    print_r($expectedValue, true),
                    print_r($item[$attributeColorKey], true),
                )
            );

            $attributeSizeKey = 'athos_size_multi';
            $this->assertArrayHasKey(
                $attributeSizeKey,
                $item,
                sprintf('Attribute "%s" is missing', $attributeSizeKey)
            );

            $this->assertNotNull(
                $item[$attributeSizeKey],
                sprintf('Attribute "%s" value is null', $attributeSizeKey)
            );
            $expectedValue = 'S,M,L,5XL,3XL';
            $this->assertContains(
                $expectedValue,
                $item,
                sprintf(
                    'Attribute "%s" value mismatch. Expected "%s", got "%s"',
                    $attributeSizeKey,
                    print_r($expectedValue, true),
                    print_r($item[$attributeSizeKey], true),
                )
            );
        }
    }


    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_boolean_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_decimal_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_material_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_brand_as_select_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_color_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/product_size_as_multiselect_attribute.php
     * @magentoDataFixture AthosCommerce_Feed::Test/_files/simple/01_simple_products_with_attributes.php
     */
    public function testReset(): void
    {
        $specification = $this->specificationBuilder->build([]);
        $products = $this->getProducts->get($specification);
        $this->attributesProvider->getData($products, $specification);
        $this->attributesProvider->reset();
        $this->assertTrue(true);
    }
}
