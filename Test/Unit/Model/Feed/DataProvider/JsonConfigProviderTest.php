<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\JsonConfigProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Helper\Data as ConfigurableHelper;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchRenderer;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Swatches\Model\SwatchAttributesProvider;
use PHPUnit\Framework\TestCase;

class JsonConfigProviderTest extends TestCase
{
    private $productRepositoryMock;
    private $configurableResourceMock;
    private $configurableHelperMock;
    private $configurableAttributeDataMock;
    private $configurableTypeMock;
    private $swatchHelperMock;
    private $loggerMock;
    private $swatchAttributesProviderMock;
    private $swatchRendererMock;
    private $jsonConfigProvider;

    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->configurableResourceMock = $this->createMock(ConfigurableResource::class);
        $this->configurableHelperMock = $this->createMock(ConfigurableHelper::class);
        $this->configurableAttributeDataMock = $this->createMock(ConfigurableAttributeData::class);
        $this->configurableTypeMock = $this->createMock(ConfigurableType::class);
        $this->swatchHelperMock = $this->createMock(SwatchHelper::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->swatchAttributesProviderMock = $this->createMock(SwatchAttributesProvider::class);
        $this->swatchRendererMock = $this->getMockBuilder(SwatchRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setProduct', 'getJsonConfig'])
            ->getMock();

        $this->jsonConfigProvider = new JsonConfigProvider(
            $this->productRepositoryMock,
            $this->configurableResourceMock,
            $this->configurableHelperMock,
            $this->configurableAttributeDataMock,
            $this->configurableTypeMock,
            $this->swatchHelperMock,
            $this->loggerMock,
            $this->swatchAttributesProviderMock,
            $this->swatchRendererMock
        );
    }

    public function testGetData(): void
    {
        $simpleProductMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parentProductMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $products = [
            [
                'product_model' => $simpleProductMock,
            ],
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $simpleProductMock->expects($this->once())
            ->method('getId')
            ->willReturn(10);

        $this->configurableResourceMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with(10)
            ->willReturn([100]);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn($parentProductMock);

        $allowedProducts = [$simpleProductMock];
        $options = [
            'index' => ['10' => ['size' => 'M']],
            'salable' => ['10' => true],
        ];
        $attributesData = [
            'attributes' => ['size' => ['id' => 1]],
        ];

        $this->configurableTypeMock->expects($this->once())
            ->method('getUsedProducts')
            ->with($parentProductMock)
            ->willReturn($allowedProducts);

        $this->configurableHelperMock->expects($this->once())
            ->method('getOptions')
            ->with($parentProductMock, $allowedProducts)
            ->willReturn($options);

        $this->configurableAttributeDataMock->expects($this->once())
            ->method('getAttributesData')
            ->with($parentProductMock, $options)
            ->willReturn($attributesData);

        $this->swatchRendererMock->expects($this->once())
            ->method('setProduct')
            ->with($parentProductMock)
            ->willReturnSelf();

        $this->swatchRendererMock->expects($this->once())
            ->method('getJsonConfig')
            ->willReturn('{testsw: testsw}');

        $result = $this->jsonConfigProvider->getData($products, $feedSpecificationMock);

        $this->assertSame($simpleProductMock, $result[0]['product_model']);
        $this->assertSame('{testsw: testsw}', $result[0]['swatch_json_config']);

        $decoded = json_decode($result[0]['json_config'], true);
        $this->assertSame(['size' => ['id' => 1]], $decoded['attributes']);
        $this->assertSame(['10' => ['size' => 'M']], $decoded['index']);
        $this->assertSame(['10' => true], $decoded['salable']);
        $this->assertSame(100, $decoded['productId']);
    }
}
