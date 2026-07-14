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

namespace AthosCommerce\Feed\Test\Unit\Model;

use AthosCommerce\Feed\Model\Api\GetStoresInfo;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\View;
use Magento\Framework\View\ConfigInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetStoresInfoTest extends TestCase
{
    /**
     * @var StoreManagerInterface&MockObject
     */
    private $storeManagerMock;

    /**
     * @var ConfigInterface&MockObject
     */
    private $viewConfigMock;

    /**
     * @var Emulation&MockObject
     */
    private $emulationMock;

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private $scopeConfigMock;

    /**
     * @var GetStoresInfo
     */
    private $getStoresInfoModel;

    public function setUp(): void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->viewConfigMock = $this->createMock(ConfigInterface::class);
        $this->emulationMock = $this->createMock(Emulation::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->getStoresInfoModel = new GetStoresInfo(
            $this->storeManagerMock,
            $this->viewConfigMock,
            $this->emulationMock,
            $this->scopeConfigMock
        );
    }

    public function testGetAsHtml(): void
    {
        $viewMock = $this->createMock(View::class);
        $storeMock = $this->createMock(Store::class);
        $storeMockSecond = $this->createMock(Store::class);

        $firstStoreImages = [
            'image_test_1' => [
                'width' => 100,
                'height' => 200,
            ],
            'image_test_2' => [
                'width' => 300,
                'height' => 400,
            ],
            'image_test_3' => [
                'width' => 500,
                'height' => 600,
            ],
        ];

        $secondStoreImages = [
            'image_test_1_second' => [
                'width' => 110,
                'height' => 210,
            ],
            'image_test_2_second' => [
                'width' => 310,
                'height' => 410,
            ],
            'image_test_3_second' => [
                'width' => 510,
                'height' => 610,
            ],
        ];

        $expectedResult = '<h1>Stores</h1><ul>'
            . '<li>1 . default - default</li>'
            . '<h2>Store Images</h2><ul>'
            . '<li>image_test_1<ul><li>width = 100</li><li>height = 200</li></ul></li>'
            . '<li>image_test_2<ul><li>width = 300</li><li>height = 400</li></ul></li>'
            . '<li>image_test_3<ul><li>width = 500</li><li>height = 600</li></ul></li>'
            . '</ul>'
            . '<li>2 . second - second</li>'
            . '<h2>Store Images</h2><ul>'
            . '<li>image_test_1_second<ul><li>width = 110</li><li>height = 210</li></ul></li>'
            . '<li>image_test_2_second<ul><li>width = 310</li><li>height = 410</li></ul></li>'
            . '<li>image_test_3_second<ul><li>width = 510</li><li>height = 610</li></ul></li>'
            . '</ul>'
            . '</ul>';

        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->willReturn([$storeMock, $storeMockSecond]);

        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $storeMock->expects($this->once())
            ->method('getName')
            ->willReturn('default');

        $storeMock->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $storeMockSecond->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $storeMockSecond->expects($this->once())
            ->method('getName')
            ->willReturn('second');

        $storeMockSecond->expects($this->once())
            ->method('getCode')
            ->willReturn('second');

        $emulatedStoreIds = [];
        $this->emulationMock->expects($this->exactly(2))
            ->method('startEnvironmentEmulation')
            ->willReturnCallback(function ($storeId, $area, $force) use (&$emulatedStoreIds) {
                $emulatedStoreIds[] = $storeId;
                $this->assertSame(Area::AREA_FRONTEND, $area);
                $this->assertTrue($force);
            });

        $this->emulationMock->expects($this->exactly(2))
            ->method('stopEnvironmentEmulation');

        $this->viewConfigMock->expects($this->exactly(2))
            ->method('getViewConfig')
            ->willReturn($viewMock);

        $readCalls = 0;
        $viewMock->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function () use (&$readCalls, $firstStoreImages, $secondStoreImages) {
                $readCalls++;

                return [
                    'media' => [
                        'Magento_Catalog' => [
                            'images' => $readCalls === 1 ? $firstStoreImages : $secondStoreImages,
                        ],
                    ],
                ];
            });

        $this->assertSame($expectedResult, $this->getStoresInfoModel->getAsHtml());
        $this->assertSame([1, 2], $emulatedStoreIds);
    }

    public function testGetAsJson(): void
    {
        $viewMock = $this->createMock(View::class);
        $storeMock = $this->createMock(Store::class);
        $storeMockSecond = $this->createMock(Store::class);

        $firstStoreImages = [
            [
                'image_test_1' => [
                    'width' => 100,
                    'height' => 200,
                ],
                'image_test_2' => [
                    'width' => 300,
                    'height' => 400,
                ],
                'image_test_3' => [
                    'width' => 500,
                    'height' => 600,
                ],
            ],
        ];

        $secondStoreImages = [
            [
                'image_test_1_second' => [
                    'width' => 110,
                    'height' => 210,
                ],
                'image_test_2_second' => [
                    'width' => 310,
                    'height' => 410,
                ],
                'image_test_3_second' => [
                    'width' => 510,
                    'height' => 610,
                ],
            ],
        ];

        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->willReturn([$storeMock, $storeMockSecond]);

        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $storeMock->expects($this->once())
            ->method('getName')
            ->willReturn('default');

        $storeMock->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $storeMockSecond->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $storeMockSecond->expects($this->once())
            ->method('getName')
            ->willReturn('second');

        $storeMockSecond->expects($this->once())
            ->method('getCode')
            ->willReturn('second');

        $themeConfigCalls = [];
        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnCallback(function ($path, $scopeType, $scopeCode) use (&$themeConfigCalls) {
                $themeConfigCalls[] = [$path, $scopeType, $scopeCode];

                if ($scopeCode === 1) {
                    return 3;
                }

                if ($scopeCode === 2) {
                    return 6;
                }

                return null;
            });

        $emulatedStoreIds = [];
        $this->emulationMock->expects($this->exactly(2))
            ->method('startEnvironmentEmulation')
            ->willReturnCallback(function ($storeId, $area, $force) use (&$emulatedStoreIds) {
                $emulatedStoreIds[] = $storeId;
                $this->assertSame(Area::AREA_FRONTEND, $area);
                $this->assertTrue($force);
            });

        $this->emulationMock->expects($this->exactly(2))
            ->method('stopEnvironmentEmulation');

        $this->viewConfigMock->expects($this->exactly(2))
            ->method('getViewConfig')
            ->willReturn($viewMock);

        $readCalls = 0;
        $viewMock->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(function () use (&$readCalls, $firstStoreImages, $secondStoreImages) {
                $readCalls++;

                return [
                    'media' => [
                        'Magento_Catalog' => [
                            'images' => $readCalls === 1 ? $firstStoreImages : $secondStoreImages,
                        ],
                    ],
                ];
            });

        $this->assertSame(
            [
                [
                    'store_id' => 1,
                    'name' => 'default',
                    'code' => 'default',
                    'theme_id' => 3,
                    'images' => $firstStoreImages,
                ],
                [
                    'store_id' => 2,
                    'name' => 'second',
                    'code' => 'second',
                    'theme_id' => 6,
                    'images' => $secondStoreImages,
                ],
            ],
            $this->getStoresInfoModel->getAsJson()
        );

        $this->assertSame(
            [
                [DesignInterface::XML_PATH_THEME_ID, ScopeInterface::SCOPE_STORE, 1],
                [DesignInterface::XML_PATH_THEME_ID, ScopeInterface::SCOPE_STORE, 2],
            ],
            $themeConfigCalls
        );

        $this->assertSame([1, 2], $emulatedStoreIds);
    }
}
