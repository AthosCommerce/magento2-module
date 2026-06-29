<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed\Exclusion;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface as FeedSpecification;
use AthosCommerce\Feed\Model\Feed\Filter\FeedItemFilterInterface;
use Magento\Catalog\Model\Product;

class ResolverFieldsRowSanitizer implements FeedItemFilterInterface
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
    public function shouldExcludeEntity(Product $product, FeedSpecification $feedSpecification): bool
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
        foreach ($rows as &$row) {
            $row = $this->sanitizeRow($row);
        }
        unset($row);

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
     * @param array $row
     * @return array
     */
    private function sanitizeRow(array $row): array
    {
        foreach (array_keys($row) as $key) {
            if (strpos((string)$key, '__resolved') === 0) {
                unset($row[$key]);
            }
        }

        return $row;
    }
}
