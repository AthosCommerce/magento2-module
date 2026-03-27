<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Exclusion;

use AthosCommerce\Feed\Model\Feed\Filter\FeedItemFilterInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use AthosCommerce\Feed\Model\Feed\ProductExclusionInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface as FeedSpecification;

class VisibilityExclusion implements FeedItemFilterInterface
{
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
        $productModel = $row['product_model'] ?? null;
        if (!$productModel instanceof Product) {
            return false;
        }

        // Excluding products that are disabled AND not visible individually
        if ($productModel->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE &&
            in_array($productModel->getTypeId(), $this->getProductTypesToExclude(), true)
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function getProductTypesToExclude(): array
    {
        return ['grouped', 'bundle'];
    }
}
