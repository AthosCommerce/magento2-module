<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\VariantAdditionalDataProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use PHPUnit\Framework\TestCase;

class VariantAdditionalDataProviderTest extends TestCase
{
    /**
     * @var ParentVariantResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentVariantResolverMock;

    /**
     * @var MetadataPool|\PHPUnit\Framework\MockObject\MockObject
     */
    private $metadataPoolMock;

    /**
     * @var EntityMetadataInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityMetadataMock;

    /**
     * @var AthosCommerceLogger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var VariantAdditionalDataProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->entityMetadataMock = $this->createMock(EntityMetadataInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->metadataPoolMock->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($this->entityMetadataMock);

        $this->entityMetadataMock->method('getLinkField')
            ->willReturn('row_id');

        $this->provider = new VariantAdditionalDataProvider(
            $this->parentVariantResolverMock,
            $this->metadataPoolMock,
            $this->loggerMock
        );
    }

    public function testGetDataReturnsOriginalProductsWhenFieldIgnored(): void
    {
        $specificationMock = $this->createSpecificationMock(
            [VariantAdditionalDataProvider::FIELD_KEY],
            [],
            200
        );

        $productMock = $this->createProductMock(11, 1011, 'simple', 'simple-sku');
        $products = [
            [
                'product_model' => $productMock,
                'entity_id' => 11,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);

        $this->assertSame($products, $result);
    }

    public function testGetDataReturnsEmptyArrayWhenParentCannotBeResolved(): void
    {
        $childProductMock = $this->createProductMock(12, 1012, 'simple', 'child-sku');

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->willReturn(null);

        $specificationMock = $this->createSpecificationMock([], [], 200);
        $products = [
            [
                'product_model' => $childProductMock,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);

        $this->assertSame([], $result[0][VariantAdditionalDataProvider::FIELD_KEY]);
    }

    public function testGetDataBuildsVariantAdditionalDataForConfigurableParent(): void
    {
        $parentProductMock = $this->createProductMock(100, 1100, Constant::CONFIGURABLE_TYPE, 'parent-config');
        $childOneMock = $this->createChildProductMock(21, 2021, 'child-one', true, ['price' => '107.00']);
        $childTwoMock = $this->createChildProductMock(22, 2022, 'child-two', false, ['price' => '108.00']);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->with($parentProductMock)
            ->willReturn([$childOneMock, $childTwoMock]);

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('getVariantOptions')
            ->willReturnOnConsecutiveCalls(
                [
                    'color' => ['value' => 'Olive'],
                    'size' => ['value' => 'L'],
                ],
                [
                    'color' => ['value' => 'Navy'],
                    'size' => ['value' => 'M'],
                ]
            );

        $specificationMock = $this->createSpecificationMock([], ['price'], 200);

        $products = [
            [
                'product_model' => $parentProductMock,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);
        $rows = $result[0][VariantAdditionalDataProvider::FIELD_KEY];

        $this->assertCount(2, $rows);

        $this->assertSame('2021', $rows[0]['variant_id']);
        $this->assertSame(
            [
                'color' => 'Olive',
                'size' => 'L',
            ],
            $rows[0]['options']
        );
        $this->assertTrue($rows[0]['available']);
        $this->assertSame('107.00', $rows[0]['price']);

        $this->assertSame('2022', $rows[1]['variant_id']);
        $this->assertSame(
            [
                'color' => 'Navy',
                'size' => 'M',
            ],
            $rows[1]['options']
        );
        $this->assertFalse($rows[1]['available']);
        $this->assertSame('108.00', $rows[1]['price']);
    }

    public function testGetDataBuildsVariantAdditionalDataForGroupedParentWithEmptyOptions(): void
    {
        $parentProductMock = $this->createProductMock(200, 2200, Constant::GROUPED_TYPE, 'parent-grouped');
        $childProductMock = $this->createChildProductMock(31, 3031, 'grouped-child', true, []);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->with($parentProductMock)
            ->willReturn([$childProductMock]);

        $this->parentVariantResolverMock->expects($this->never())
            ->method('getVariantOptions');

        $specificationMock = $this->createSpecificationMock([], [], 200);

        $products = [
            [
                'product_model' => $parentProductMock,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);
        $rows = $result[0][VariantAdditionalDataProvider::FIELD_KEY];

        $this->assertCount(1, $rows);
        $this->assertSame('3031', $rows[0]['variant_id']);
        $this->assertSame([], $rows[0]['options']);
        $this->assertTrue($rows[0]['available']);
    }

    public function testGetDataUsesVariantLimitFromSpecification(): void
    {
        $parentProductMock = $this->createProductMock(300, 3300, Constant::CONFIGURABLE_TYPE, 'parent-limit');
        $childOneMock = $this->createChildProductMock(41, 4041, 'child-1', true, []);
        $childTwoMock = $this->createChildProductMock(42, 4042, 'child-2', true, []);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->willReturn([$childOneMock, $childTwoMock]);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->willReturn(['color' => ['value' => 'Olive']]);

        $specificationMock = $this->createSpecificationMock([], [], 1);

        $products = [
            [
                'product_model' => $parentProductMock,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);
        $rows = $result[0][VariantAdditionalDataProvider::FIELD_KEY];

        $this->assertCount(1, $rows);
        $this->assertSame('4041', $rows[0]['variant_id']);
    }

    public function testGetDataFallsBackToDefaultVariantLimitWhenSpecificationLimitIsInvalid(): void
    {
        $parentProductMock = $this->createProductMock(400, 4400, Constant::CONFIGURABLE_TYPE, 'parent-default-limit');
        $childOneMock = $this->createChildProductMock(51, 5051, 'child-1', true, []);
        $childTwoMock = $this->createChildProductMock(52, 5052, 'child-2', true, []);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->willReturn([$childOneMock, $childTwoMock]);

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('getVariantOptions')
            ->willReturn(['color' => ['value' => 'Olive']]);

        $specificationMock = $this->createSpecificationMock([], [], 0);

        $products = [
            [
                'product_model' => $parentProductMock,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);
        $rows = $result[0][VariantAdditionalDataProvider::FIELD_KEY];

        $this->assertCount(2, $rows);
    }

    public function testGetDataLimitsAdditionalFieldsToFiveAndSkipsReservedFields(): void
    {
        $parentProductMock = $this->createProductMock(500, 5500, Constant::CONFIGURABLE_TYPE, 'parent-additional');
        $childProductMock = $this->createChildProductMock(
            61,
            6061,
            'child-additional',
            true,
            [
                'price' => '10.00',
                'special_price' => '9.00',
                'brand' => 'Athos',
                'material' => 'Cotton',
                'pattern' => 'Solid',
                'season' => 'Summer',
            ]
        );

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->willReturn([$childProductMock]);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->willReturn(['color' => ['value' => 'Black']]);

        $specificationMock = $this->createSpecificationMock(
            [],
            ['variant_id', 'options', 'available', 'price', 'special_price', 'brand', 'material', 'pattern', 'season'],
            200
        );

        $products = [
            [
                'product_model' => $parentProductMock,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);
        $row = $result[0][VariantAdditionalDataProvider::FIELD_KEY][0];

        $this->assertArrayHasKey('price', $row);
        $this->assertArrayHasKey('special_price', $row);
        $this->assertArrayHasKey('brand', $row);
        $this->assertArrayHasKey('material', $row);
        $this->assertArrayHasKey('pattern', $row);
        $this->assertArrayNotHasKey('season', $row);

        $this->assertCount(8, $row);
    }

    public function testGetDataFallsBackToEntityIdWhenLinkFieldValueIsMissing(): void
    {
        $parentProductMock = $this->createProductMock(600, 6600, Constant::CONFIGURABLE_TYPE, 'parent-fallback');
        $childProductMock = $this->createChildProductMock(71, null, 'child-fallback', true, []);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->willReturn([$childProductMock]);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getVariantOptions')
            ->willReturn(['size' => ['value' => 'M']]);

        $specificationMock = $this->createSpecificationMock([], [], 200);

        $products = [
            [
                'product_model' => $parentProductMock,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);
        $row = $result[0][VariantAdditionalDataProvider::FIELD_KEY][0];

        $this->assertSame('71', $row['variant_id']);
    }

    public function testGetDataReturnsEmptyArrayWhenUnsupportedParentType(): void
    {
        $productMock = $this->createProductMock(700, 7700, 'simple', 'simple-unsupported');

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->willReturn($productMock);

        $specificationMock = $this->createSpecificationMock([], [], 200);

        $products = [
            [
                'product_model' => $productMock,
            ]
        ];

        $result = $this->provider->getData($products, $specificationMock);

        $this->assertSame([], $result[0][VariantAdditionalDataProvider::FIELD_KEY]);
    }

    /**
     * @param array $ignoreFields
     * @param array $additionalFields
     * @param int|null $variantLimit
     * @return FeedSpecificationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSpecificationMock(
        array $ignoreFields,
        array $additionalFields,
        ?int  $variantLimit
    )
    {
        $specificationMock = $this->createMock(FeedSpecificationInterface::class);

        $specificationMock->method('getIgnoreFields')
            ->willReturn($ignoreFields);

        $specificationMock->method('getVariantAdditionalFields')
            ->willReturn($additionalFields);

        $specificationMock->method('getVariantAdditionalDataLimit')
            ->willReturn($variantLimit);

        return $specificationMock;
    }

    /**
     * @param int $id
     * @param int|null $rowId
     * @param string $typeId
     * @param string $sku
     * @return Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createProductMock(
        int    $id,
               $rowId,
        string $typeId,
        string $sku
    )
    {
        $productMock = $this->createMock(Product::class);

        $productMock->method('getId')
            ->willReturn($id);

        $productMock->method('getTypeId')
            ->willReturn($typeId);

        $productMock->method('getSku')
            ->willReturn($sku);

        $productMock->method('getData')
            ->willReturnCallback(function ($field) use ($rowId) {
                if ($field === 'row_id') {
                    return $rowId;
                }

                return null;
            });

        return $productMock;
    }

    /**
     * @param int $id
     * @param int|null $rowId
     * @param string $sku
     * @param bool $available
     * @param array $data
     * @return Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createChildProductMock(
        int    $id,
               $rowId,
        string $sku,
        bool   $available,
        array  $data
    )
    {
        $productMock = $this->createMock(Product::class);

        $productMock->method('getId')
            ->willReturn($id);

        $productMock->method('getSku')
            ->willReturn($sku);

        $productMock->method('isAvailable')
            ->willReturn($available);

        $productMock->method('getData')
            ->willReturnCallback(function ($field) use ($rowId, $data) {
                if ($field === 'row_id') {
                    return $rowId;
                }

                return $data[$field] ?? null;
            });

        return $productMock;
    }
}
