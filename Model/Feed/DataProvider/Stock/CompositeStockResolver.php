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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Stock;

use Magento\Framework\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class CompositeStockResolver implements StockResolverInterface
{
    /**
     * @var array
     */
    private $resolvers;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * CompositeStockResolver constructor.
     *
     * @param array $resolvers
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        AthosCommerceLogger $logger,
        array $resolvers = []
    ) {
        $this->resolvers = $resolvers;
        $this->logger = $logger;
    }

    /**
     * @return StockProviderInterface
     * @throws NoSuchEntityException
     */
    public function resolve(bool $isMsiEnabled): StockProviderInterface
    {
        $sortedResolvers = $this->sort($this->resolvers);
        $provider = null;
        foreach ($sortedResolvers as $resolver) {
            /** @var StockResolverInterface $resolverInstance */
            $resolverInstance = $resolver['objectInstance'] ?? null;
            if (!$resolverInstance) {
                continue;
            }

            try {
                $provider = $resolverInstance->resolve($isMsiEnabled);
            } catch (NoSuchEntityException $exception) {
                $this->logger->error(
                    "Could not resolve stock provider for feed generation",
                    [
                        'method' => __METHOD__,
                        'exception' => $exception,
                    ]
                );
            }

            if ($provider) {
                break;
            }
        }

        if (!$provider) {
            throw new NoSuchEntityException(
                __('There is no stock provider available for feed generation')
            );
        }

        return $provider;
    }

    /**
     * Sorting modifiers according to sort order
     *
     * @param array $data
     *
     * @return array
     */
    private function sort(array $data)
    {
        usort($data, function (array $a, array $b) {
            return $this->getSortOrder($a) <=> $this->getSortOrder($b);
        });

        return $data;
    }

    /**
     * Retrieve sort order from array
     *
     * @param array $variable
     *
     * @return int
     */
    private function getSortOrder(array $variable)
    {
        return !empty($variable['sortOrder'])
            ? $variable['sortOrder']
            : 0;
    }
}
