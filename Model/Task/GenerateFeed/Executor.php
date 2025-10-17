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

namespace AthosCommerce\Feed\Model\Task\GenerateFeed;

use Magento\Framework\Exception\CouldNotSaveException;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\GenerateFeedInterface;
use AthosCommerce\Feed\Exception\GenericException;
use AthosCommerce\Feed\Model\Feed\SpecificationBuilderInterface;
use AthosCommerce\Feed\Model\Task\ExecutorInterface;

class Executor implements ExecutorInterface
{
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var GenerateFeedInterface
     */
    private $generateFeed;

    /**
     * Executor constructor.
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param GenerateFeedInterface $generateFeed
     */
    public function __construct(
        SpecificationBuilderInterface $specificationBuilder,
        GenerateFeedInterface $generateFeed
    ) {
        $this->specificationBuilder = $specificationBuilder;
        $this->generateFeed = $generateFeed;
    }

    /**
     * @param TaskInterface $task
     * @return void
     * @throws GenericException
     */
    public function execute(TaskInterface $task)
    {
        $specification = $this->specificationBuilder->build($task->getPayload());
        $this->generateFeed->execute($specification, $task->getEntityId());
    }
}
