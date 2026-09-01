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

namespace AthosCommerce\Feed\Api\Data;

if (!interface_exists(FeedSpecificationExtensionInterface::class, false)) {
    interface FeedSpecificationExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
    {
    }
}

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Specification;

use AthosCommerce\Feed\Api\Data\FeedSpecificationExtensionInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Api\Data\MediaGallerySpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;

class FeedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionFactoryMock;

    /**
     * @var AttributeValueFactory
     */
    private $attributeValueFactoryMock;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->extensionFactoryMock = $this->createMock(ExtensionAttributesFactory::class);
        $this->attributeValueFactoryMock = $this->createMock(AttributeValueFactory::class);
    }

    /**
     * @param array $data
     * @return Feed
     */
    private function createSpecification(array $data = array())
    {
        return new Feed(
            $this->extensionFactoryMock,
            $this->attributeValueFactoryMock,
            $data
        );
    }

    public function testGetStoreCodeReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getStoreCode());
    }

    public function testSetStoreCodeAndGetStoreCode()
    {
        $specification = $this->createSpecification();

        $result = $specification->setStoreCode('default');

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame('default', $specification->getStoreCode());
    }

    public function testGetHierarchySeparatorReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getHierarchySeparator());
    }

    public function testSetHierarchySeparatorAndGetHierarchySeparator()
    {
        $specification = $this->createSpecification();

        $result = $specification->setHierarchySeparator('/');

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame('/', $specification->getHierarchySeparator());
    }

    public function testGetMultiValuedSeparatorReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getMultiValuedSeparator());
    }

    public function testSetMultiValuedSeparatorAndGetMultiValuedSeparator()
    {
        $specification = $this->createSpecification();

        $result = $specification->setMultiValuedSeparator('|');

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame('|', $specification->getMultiValuedSeparator());
    }

    public function testGetIncludeUrlHierarchyReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getIncludeUrlHierarchy());
    }

    public function testSetIncludeUrlHierarchyTrueAndGetIncludeUrlHierarchy()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeUrlHierarchy(true);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertTrue($specification->getIncludeUrlHierarchy());
    }

    public function testSetIncludeUrlHierarchyFalseAndGetIncludeUrlHierarchy()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeUrlHierarchy(false);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertFalse($specification->getIncludeUrlHierarchy());
    }

    public function testGetIncludeUrlHierarchyCastsRawDataToBool()
    {
        $trueSpecification = $this->createSpecification(array(
            FeedSpecificationInterface::INCLUDE_URL_HIERARCHY => 1
        ));
        $falseSpecification = $this->createSpecification(array(
            FeedSpecificationInterface::INCLUDE_URL_HIERARCHY => 0
        ));

        $this->assertTrue($trueSpecification->getIncludeUrlHierarchy());
        $this->assertFalse($falseSpecification->getIncludeUrlHierarchy());
    }

    public function testGetIncludeMenuCategoriesReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getIncludeMenuCategories());
    }

    public function testSetIncludeMenuCategoriesTrueAndGetIncludeMenuCategories()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeMenuCategories(true);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertTrue($specification->getIncludeMenuCategories());
    }

    public function testSetIncludeMenuCategoriesFalseAndGetIncludeMenuCategories()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeMenuCategories(false);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertFalse($specification->getIncludeMenuCategories());
    }

    public function testGetIncludeJsonConfigReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getIncludeJSONConfig());
    }

    public function testSetIncludeJsonConfigTrueAndGetIncludeJsonConfig()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeJSONConfig(true);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertTrue($specification->getIncludeJSONConfig());
    }

    public function testSetIncludeJsonConfigFalseAndGetIncludeJsonConfig()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeJSONConfig(false);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertFalse($specification->getIncludeJSONConfig());
    }

    public function testGetIncludeChildPricesReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getIncludeChildPrices());
    }

    public function testSetIncludeChildPricesTrueAndGetIncludeChildPrices()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeChildPrices(true);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertTrue($specification->getIncludeChildPrices());
    }

    public function testSetIncludeChildPricesFalseAndGetIncludeChildPrices()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeChildPrices(false);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertFalse($specification->getIncludeChildPrices());
    }

    public function testGetIncludeTierPricingReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getIncludeTierPricing());
    }

    public function testSetIncludeTierPricingTrueAndGetIncludeTierPricing()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeTierPricing(true);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertTrue($specification->getIncludeTierPricing());
    }

    public function testSetIncludeTierPricingFalseAndGetIncludeTierPricing()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeTierPricing(false);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertFalse($specification->getIncludeTierPricing());
    }

    public function testGetCustomerIdReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getCustomerId());
    }

    public function testSetCustomerIdAndGetCustomerId()
    {
        $specification = $this->createSpecification();

        $result = $specification->setCustomerId(42);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame(42, $specification->getCustomerId());
    }

    public function testGetCustomerIdCastsRawDataToInt()
    {
        $specification = $this->createSpecification(array(
            FeedSpecificationInterface::CUSTOMER_ID => '42'
        ));

        $this->assertSame(42, $specification->getCustomerId());
    }

    public function testGetCustomerReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getCustomer());
    }

    public function testSetCustomerAndGetCustomer()
    {
        $customerMock = $this->createMock(CustomerInterface::class);
        $specification = $this->createSpecification();

        $result = $specification->setCustomer($customerMock);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame($customerMock, $specification->getCustomer());
    }

    public function testGetChildFieldsReturnsEmptyArrayByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertSame(array(), $specification->getChildFields());
    }

    public function testSetChildFieldsAndGetChildFields()
    {
        $fields = array('sku', 'name');
        $specification = $this->createSpecification();

        $result = $specification->setChildFields($fields);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame($fields, $specification->getChildFields());
    }

    public function testGetIncludeOutOfStockReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getIncludeOutOfStock());
    }

    public function testSetIncludeOutOfStockTrueAndGetIncludeOutOfStock()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeOutOfStock(true);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertTrue($specification->getIncludeOutOfStock());
    }

    public function testSetIncludeOutOfStockFalseAndGetIncludeOutOfStock()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeOutOfStock(false);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertFalse($specification->getIncludeOutOfStock());
    }

    public function testGetIgnoreFieldsReturnsEmptyArrayByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertSame(array(), $specification->getIgnoreFields());
    }

    public function testSetIgnoreFieldsAndGetIgnoreFields()
    {
        $fields = array('price', 'special_price');
        $specification = $this->createSpecification();

        $result = $specification->setIgnoreFields($fields);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame($fields, $specification->getIgnoreFields());
    }

    public function testGetFormatReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getFormat());
    }

    public function testSetFormatAndGetFormat()
    {
        $specification = $this->createSpecification();

        $result = $specification->setFormat('json');

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame('json', $specification->getFormat());
    }

    public function testGetMediaGallerySpecificationReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getMediaGallerySpecification());
    }

    public function testSetMediaGallerySpecificationAndGetMediaGallerySpecification()
    {
        $mediaGallerySpecificationMock = $this->createMock(MediaGallerySpecificationInterface::class);
        $specification = $this->createSpecification();

        $result = $specification->setMediaGallerySpecification($mediaGallerySpecificationMock);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame($mediaGallerySpecificationMock, $specification->getMediaGallerySpecification());
    }

    public function testSetExtensionAttributesAndGetExtensionAttributes()
    {
        $extensionAttributesMock = $this->createMock(FeedSpecificationExtensionInterface::class);
        $specification = $this->createSpecification();

        $result = $specification->setExtensionAttributes($extensionAttributesMock);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame($extensionAttributesMock, $specification->getExtensionAttributes());
    }

    public function testGetPreSignedUrlReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getPreSignedUrl());
    }

    public function testSetPreSignedUrlAndGetPreSignedUrl()
    {
        $specification = $this->createSpecification();

        $result = $specification->setPreSignedUrl('https://example.com/feed.json');

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame('https://example.com/feed.json', $specification->getPreSignedUrl());
    }

    public function testGetIsMsiEnabledReturnsFalseByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertFalse($specification->getIsMsiEnabled());
    }

    public function testSetIsMsiEnabledTrueAndGetIsMsiEnabled()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIsMsiEnabled(true);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertTrue($specification->getIsMsiEnabled());
    }

    public function testSetIsMsiEnabledFalseAndGetIsMsiEnabled()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIsMsiEnabled(false);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertFalse($specification->getIsMsiEnabled());
    }

    public function testGetSwatchOptionFieldsNamesReturnsEmptyArrayByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertSame(array(), $specification->getSwatchOptionFieldsNames());
    }

    public function testSetSwatchOptionFieldsNamesAndGetSwatchOptionFieldsNames()
    {
        $fields = array('color', 'pattern');
        $specification = $this->createSpecification();

        $result = $specification->setSwatchOptionFieldsNames($fields);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame($fields, $specification->getSwatchOptionFieldsNames());
    }

    public function testGetVariantAdditionalFieldsReturnsEmptyArrayByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertSame(array(), $specification->getVariantAdditionalFields());
    }

    public function testSetVariantAdditionalFieldsAndGetVariantAdditionalFields()
    {
        $fields = array('size', 'material');
        $specification = $this->createSpecification();

        $result = $specification->setVariantAdditionalFields($fields);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame($fields, $specification->getVariantAdditionalFields());
    }

    public function testGetIndexingModeReturnsBulkModeByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertSame(FeedSpecificationInterface::BULK_MODE, $specification->getIndexingMode());
    }

    public function testSetIndexingModeAndGetIndexingMode()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIndexingMode(FeedSpecificationInterface::LIVE_MODE);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame(FeedSpecificationInterface::LIVE_MODE, $specification->getIndexingMode());
    }

    public function testGetAdditionalIgnoreFieldsByModeReturnsLiveModeConstants()
    {
        $specification = $this->createSpecification();
        $specification->setIndexingMode(FeedSpecificationInterface::LIVE_MODE);

        $this->assertSame(
            FeedSpecificationInterface::LIVE_INDEXING_IGNORE_DATA_PROVIDERS,
            $specification->getAdditionalIgnoreFieldsByMode()
        );
    }

    public function testGetAdditionalIgnoreFieldsByModeReturnsBulkModeConstantsByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertSame(
            FeedSpecificationInterface::BULK_INDEXING_IGNORE_DATA_PROVIDERS,
            $specification->getAdditionalIgnoreFieldsByMode()
        );
    }

    public function testGetAdditionalIgnoreFieldsByModeReturnsBulkModeConstantsForExplicitBulkMode()
    {
        $specification = $this->createSpecification();
        $specification->setIndexingMode(FeedSpecificationInterface::BULK_MODE);

        $this->assertSame(
            FeedSpecificationInterface::BULK_INDEXING_IGNORE_DATA_PROVIDERS,
            $specification->getAdditionalIgnoreFieldsByMode()
        );
    }

    public function testGetExcludedProductIdsReturnsEmptyArrayByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertSame(array(), $specification->getExcludedProductIds());
    }

    public function testSetExcludedProductIdsAndGetExcludedProductIds()
    {
        $productIds = array(10, 20, 30);
        $specification = $this->createSpecification();

        $result = $specification->setExcludedProductIds($productIds);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame($productIds, $specification->getExcludedProductIds());
    }

    public function testGetIncludeAllVariantsReturnsFalseByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertFalse($specification->getIncludeAllVariants());
    }

    public function testSetIncludeAllVariantsTrueAndGetIncludeAllVariants()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeAllVariants(true);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertTrue($specification->getIncludeAllVariants());
    }

    public function testSetIncludeAllVariantsFalseAndGetIncludeAllVariants()
    {
        $specification = $this->createSpecification();

        $result = $specification->setIncludeAllVariants(false);

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertFalse($specification->getIncludeAllVariants());
    }

    public function testGetParentIdSourceFieldNameReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getParentIdSourceFieldName());
    }

    public function testGetParentIdSourceFieldNameReturnsNullForEmptyString()
    {
        $specification = $this->createSpecification(array(
            FeedSpecificationInterface::PARENT_ID_SOURCE_FIELD => ''
        ));

        $this->assertNull($specification->getParentIdSourceFieldName());
    }

    public function testGetParentIdSourceFieldNameReturnsNullForWhitespaceOnlyString()
    {
        $specification = $this->createSpecification(array(
            FeedSpecificationInterface::PARENT_ID_SOURCE_FIELD => '   '
        ));

        $this->assertNull($specification->getParentIdSourceFieldName());
    }

    public function testGetParentIdSourceFieldNameReturnsTrimmedValue()
    {
        $specification = $this->createSpecification(array(
            FeedSpecificationInterface::PARENT_ID_SOURCE_FIELD => '  parent_id  '
        ));

        $this->assertSame('parent_id', $specification->getParentIdSourceFieldName());
    }

    public function testSetParentIdSourceFieldNameAndGetParentIdSourceFieldName()
    {
        $specification = $this->createSpecification();

        $result = $specification->setParentIdSourceFieldName('entity_id');

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame('entity_id', $specification->getParentIdSourceFieldName());
    }

    public function testGetGroupBySourceFieldNameReturnsNullByDefault()
    {
        $specification = $this->createSpecification();

        $this->assertNull($specification->getGroupBySourceFieldName());
    }

    public function testGetGroupBySourceFieldNameReturnsNullForEmptyString()
    {
        $specification = $this->createSpecification(array(
            FeedSpecificationInterface::GROUP_ID_SOURCE_FIELD => ''
        ));

        $this->assertNull($specification->getGroupBySourceFieldName());
    }

    public function testGetGroupBySourceFieldNameReturnsNullForWhitespaceOnlyString()
    {
        $specification = $this->createSpecification(array(
            FeedSpecificationInterface::GROUP_ID_SOURCE_FIELD => '   '
        ));

        $this->assertNull($specification->getGroupBySourceFieldName());
    }

    public function testGetGroupBySourceFieldNameReturnsTrimmedValue()
    {
        $specification = $this->createSpecification(array(
            FeedSpecificationInterface::GROUP_ID_SOURCE_FIELD => '  group_code  '
        ));

        $this->assertSame('group_code', $specification->getGroupBySourceFieldName());
    }

    public function testSetGroupBySourceFieldNameAndGetGroupBySourceFieldName()
    {
        $specification = $this->createSpecification();

        $result = $specification->setGroupBySourceFieldName('group_id');

        $this->assertInstanceOf(FeedSpecificationInterface::class, $result);
        $this->assertSame('group_id', $specification->getGroupBySourceFieldName());
    }

    public function testGetVariantAdditionalDataLimitReturnsDefaultWhenNull()
    {
        $specification = $this->createSpecification();
        $this->assertSame(200, $specification->getVariantAdditionalDataLimit());

    }

    public function testGetVariantAdditionalDataLimitReturnsDefaultWhenEmptyString()
    {
        $specification = $this->createSpecification(['variantAdditionalDataLimit' => '']);
        $this->assertSame(200, $specification->getVariantAdditionalDataLimit());
    }

    public function testGetVariantAdditionalDataLimitReturnsDefaultWhenZero()
    {
        $specification = $this->createSpecification(['variantAdditionalDataLimit' => '0']);
        $this->assertSame(200, $specification->getVariantAdditionalDataLimit());
    }

    public function testGetVariantAdditionalDataLimitReturnsDefaultWhenNegative()
    {
        $specification = $this->createSpecification(['variantAdditionalDataLimit' => '-9']);
        $this->assertSame(200, $specification->getVariantAdditionalDataLimit());
    }

    public function testGetVariantAdditionalDataLimitReturnsConfiguredValue()
    {
        $specification = $this->createSpecification(['variantAdditionalDataLimit' => 4000]);
        $value = $specification->getVariantAdditionalDataLimit();
        $this->assertSame(4000, $value);
    }

}
