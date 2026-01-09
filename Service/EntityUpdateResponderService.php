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

namespace AthosCommerce\Feed\Service;

use AthosCommerce\Feed\Service\EntityUpdateResponderServiceInterface;
use AthosCommerce\Feed\Model\Update\Entity;
use AthosCommerce\Feed\Model\Update\EntityInterface;
use AthosCommerce\Feed\Model\Update\EntityInterfaceFactory as EntityUpdateInterfaceFactory;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class EntityUpdateResponderService implements EntityUpdateResponderServiceInterface
{
    /**
     * @var EntityUpdateInterfaceFactory
     */
    private $entityUpdateFactory;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param EntityUpdateInterfaceFactory $entityUpdateFactory
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        EntityUpdateInterfaceFactory $entityUpdateFactory,
        AthosCommerceLogger $logger,
    ) {
        $this->entityUpdateFactory = $entityUpdateFactory;
        $this->logger = $logger;
    }

    /**
     * @param mixed[] $data
     *
     * @return void
     */
    public function execute(array $data): void
    {
        if (empty($data)) {
            $this->logger->debug(
                'Method: {method}, Debug: {message}',
                [
                    'method' => __METHOD__,
                    'message' => 'Empty data provided for entity update.',
                ]);

            return;
        }

        try {
            $entityUpdate = $this->createEntityUpdate($data);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error(
                'Method: {method}, Error: {message}',
                [
                    'method' => __METHOD__,
                    'message' => $exception->getMessage(),
                ],
            );

            return;
        }

        //TODO:: Execute common Action service to UPSERT/DELETE for upcoming actions
    }

    /**
     * @param mixed[] $data
     *
     * @return EntityInterface
     * @throws \InvalidArgumentException
     */
    private function createEntityUpdate(array $data): EntityInterface
    {
        return $this->entityUpdateFactory->create([
            'data' => [
                Entity::ENTITY_TYPE => '__PRODUCT',
                Entity::ENTITY_IDS => $data[Entity::ENTITY_IDS] ?? [],
            ],
        ]);
    }
}
