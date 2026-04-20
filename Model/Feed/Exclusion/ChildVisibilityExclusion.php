<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Exclusion;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\Filter\FeedItemFilterInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface as FeedSpecification;

class ChildVisibilityExclusion implements FeedItemFilterInterface
{
    /**
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ParentRelationsContext $parentRelationsContext
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ParentRelationsContext $parentRelationsContext,
        AthosCommerceLogger    $logger,
    )
    {
        $this->parentRelationsContext = $parentRelationsContext;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecification $feedSpecification
     * @return array
     */
    public function filterEntities(array $products, FeedSpecification $feedSpecification): array
    {
        return $products;
    }

    /**
     * @param Product $product
     * @param FeedSpecification $feedSpecification
     * @return bool
     */
    public function shouldExcludeEntity(
        Product           $product,
        FeedSpecification $feedSpecification
    ): bool
    {
        // Excluding products that are disabled AND not visible individually
        if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE &&
            in_array($product->getTypeId(), $this->getProductTypesToExclude(), true)
            && $this->isProductOrphan((int)$product->getId())
        ) {

            $this->logger->debug(
                sprintf(
                    'Excluding product ID %s because it is not visible individually, has type %s and is an orphan',
                    $product->getId(),
                    $product->getTypeId()
                )
            );

            return true;
        }

        return false;
    }


    /**
     * @param array $rows
     * @param FeedSpecification $feedSpecification
     * @return array
     */
    public function filterRows(array $rows, FeedSpecification $feedSpecification): array
    {
        return $rows;
    }

    /**
     * @param array $row
     * @param FeedSpecification $feedSpecification
     * @return bool
     */
    public function shouldExcludeRow(array $row, FeedSpecification $feedSpecification): bool
    {
        return false;
    }

    /**
     * @return string[]
     */
    public function getProductTypesToExclude(): array
    {
        return ['simple'];
    }

    /**
     * @param int $productId
     * @return bool
     */
    private function isProductOrphan(int $productId): bool
    {
        if ($this->parentRelationsContext->getParentsByChildId($productId) === null) {
            return true;
        }
        return false;
    }
}
