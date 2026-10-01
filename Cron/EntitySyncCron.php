<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Cron;

use AthosCommerce\Feed\Console\Command\EntitySyncCommand;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\LiveIndexing\StoreLock;
use AthosCommerce\Feed\Model\LiveIndexing\StoreWorkerLauncher;

/**
 * Starts live indexing entity sync for every store view at the same time, one process per store.
 *
 * Each worker runs `EntitySyncCommand::COMMAND_NAME --storecodes=<store>` in the background and takes
 * the store's lock, so a slow store does not delay the others and a store whose previous run is
 * still going is skipped until the next schedule.
 */
class EntitySyncCron
{
    /**
     * @var StoreWorkerLauncher
     */
    private $storeWorkerLauncher;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param StoreWorkerLauncher $storeWorkerLauncher
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        StoreWorkerLauncher $storeWorkerLauncher,
        AthosCommerceLogger $logger
    ) {
        $this->storeWorkerLauncher = $storeWorkerLauncher;
        $this->logger = $logger;
    }

    /**
     * Launch one entity sync process per store view with live indexing enabled.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('[Cron] Live indexing entity sync started');
        $stores = $this->storeWorkerLauncher->spawn(EntitySyncCommand::COMMAND_NAME, StoreLock::TYPE_SYNC);
        $this->logger->info('[Cron] Live indexing entity sync workers launched', ['stores' => $stores]);
    }
}
