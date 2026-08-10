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

namespace AthosCommerce\Feed\Model\Feed\Collection;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use InvalidArgumentException;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;

class CustomProductEntityColumnFilterModifier implements ModifierInterface
{
    /**
     * @var array
     */
    private $allowedOperators = FeedSpecificationInterface::CRITERIA_OPERATORS;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $resource
     * @param AthosCommerceLogger $logger
     * @param array $allowedOperators
     */
    public function __construct(
        ResourceConnection  $resource,
        AthosCommerceLogger $logger,
        array               $allowedOperators = []
    )
    {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->allowedOperators = !empty($allowedOperators)
            ? array_values(array_unique(array_map('strtolower', $allowedOperators)))
            : FeedSpecificationInterface::CRITERIA_OPERATORS;
    }

    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     * @return Collection
     */
    public function modify(Collection $collection, FeedSpecificationInterface $feedSpecification): Collection
    {
        $field = (string)$feedSpecification->getCustomProductEntityColumnField();
        if ($field === '') {
            return $collection;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $field)) {
            $this->logger->critical(
                '[CustomProductEntityColumnFilterModifier] Invalid custom product entity column field:',
                ['field' => $field]
            );
            throw new InvalidArgumentException(
                (string)__('Invalid custom product entity column field: %1', $field)
            );
        }

        $operator = strtolower(trim((string)$feedSpecification->getCustomProductEntityColumnOperator()));
        $value = $feedSpecification->getCustomProductEntityColumnValue();

        if (!in_array($operator, $this->allowedOperators, true)) {
            $this->logger->critical(
                '[CustomProductEntityColumnFilterModifier] Unsupported custom product entity column operator:',
                [
                    'operator' => $operator,
                    'allowed' => $this->allowedOperators,
                    'value' => $value,
                    'field' => $field
                ]
            );
            throw new InvalidArgumentException(
                (string)__(
                    'Unsupported custom product entity column operator: %1. Allowed operators are: %2',
                    $operator,
                    implode(',', $this->allowedOperators)
                )
            );
        }

        if ($value === null || $value === '') {
            $this->logger->critical(
                '[CustomProductEntityColumnFilterModifier] Custom product entity column value is required for operator:',
                [
                    'operator' => $operator,
                    'value' => $value,
                    'field' => $field
                ]
            );
            throw new InvalidArgumentException(
                (string)__(
                    'Custom product entity column value is required for operator %1',
                    $operator
                )
            );
        }

        if (($operator === 'in' || $operator === 'nin') && !is_array($value)) {
            $value = [$value];
        }

        if (($operator === 'in' || $operator === 'nin') && $value === []) {
            $this->logger->critical(
                '[CustomProductEntityColumnFilterModifier] Custom product entity column value list is required for operator:',
                [
                    'operator' => $operator,
                    'value' => $value,
                    'field' => $field
                ]
            );
            throw new InvalidArgumentException(
                (string)__(
                    'Custom product entity column value list is required for operator %1',
                    $operator
                )
            );
        }

        if (($operator !== 'in' && $operator !== 'nin') && is_array($value)) {
            $value = reset($value);
        }

        try {
            $cpe = $this->resource->getTableName('catalog_product_entity');
            $connection = $collection->getConnection();
            $condition = $connection->prepareSqlCondition('e.' . $field, [$operator => $value]);
            $collection->getSelect()->where($condition);
        } catch (\Throwable $e) {
            $this->logger->critical(
                "[CustomProductEntityColumnFilterModifier] Error applying custom product entity column filter:",
                [
                    'field' => $field,
                    'operator' => $operator,
                    'value' => $value,
                    'error' => $e->getMessage()
                ]
            );
            //return the collection without applying the filter if an error occurs, to avoid breaking the feed generation process.
            return $collection;
        }

        return $collection;
    }
}
