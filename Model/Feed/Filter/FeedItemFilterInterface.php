<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Filter;

use Magento\Catalog\Model\Product;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

interface FeedItemFilterInterface
{
    /**
     * Batch filtering of product entities
     */
    public function filterEntities(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array;

    /**
     * Per-product exclusion
     */
    public function shouldExcludeEntity(
        Product $product,
        FeedSpecificationInterface $feedSpecification
    ): bool;

    /**
     * Batch filtering of generated rows
     */
    public function filterRows(
        array $rows,
        FeedSpecificationInterface $feedSpecification
    ): array;

    /**
     * Per-row exclusion
     */
    public function shouldExcludeRow(
        array $row,
        FeedSpecificationInterface $feedSpecification
    ): bool;
}
