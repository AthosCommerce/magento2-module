<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Filter;

use Magento\Catalog\Model\Product;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface as FeedSpecification;

class FeedItemFilterPool
{
    /**
     * @var FeedItemFilterInterface[]
     */
    private array $filters;

    /**
     * @param array $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @param array $products
     * @param FeedSpecification $spec
     * @return array
     */
    public function filterEntities(
        array $products,
        FeedSpecification $spec
    ): array {
        foreach ($this->filters as $filter) {
            $products = $filter->filterEntities($products, $spec);
        }

        return $products;
    }

    /**
     * @param Product $product
     * @param FeedSpecification $spec
     * @return bool
     */
    public function shouldExcludeEntity(
        Product $product,
        FeedSpecification $spec
    ): bool {
        foreach ($this->filters as $filter) {
            if ($filter->shouldExcludeEntity($product, $spec)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $rows
     * @param FeedSpecification $spec
     * @return array
     */
    public function filterRows(
        array $rows,
        FeedSpecification $spec
    ): array {
        foreach ($this->filters as $filter) {
            $rows = $filter->filterRows($rows, $spec);
        }

        return $rows;
    }

    /**
     * @param array $row
     * @param FeedSpecification $spec
     * @return bool
     */
    public function shouldExcludeRow(
        array $row,
        FeedSpecification $spec
    ): bool {
        foreach ($this->filters as $filter) {
            if ($filter->shouldExcludeRow($row, $spec)) {
                return true;
            }
        }

        return false;
    }
}
