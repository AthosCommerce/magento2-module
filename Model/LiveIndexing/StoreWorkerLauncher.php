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

namespace AthosCommerce\Feed\Model\LiveIndexing;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Provider\LiveIndexingSiteProvider;
use Magento\Framework\Process\PhpExecutableFinderFactory;
use Magento\Framework\ShellInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Starts one background process per store view for a live indexing job, all at the same time.
 *
 * Used by the live indexing cron jobs: the cron run only launches the store workers and returns,
 * so a slow store does not delay the others. Each worker is the regular CLI command limited to one
 * store (--storecodes=<code>) and takes the store's StoreLock itself, so a store whose previous run
 * is still going is skipped.
 *
 * Processes are started the way Magento cron starts its group processes: the PHP binary comes from
 * PhpExecutableFinder, and the shell (etc/di.xml: AthosCommerceLiveIndexingShellBackground) uses
 * Magento\Framework\Shell\CommandRendererBackground to escape arguments and run in the background.
 */
class StoreWorkerLauncher
{
    /**
     * @var LiveIndexingSiteProvider
     */
    private $siteProvider;
    /**
     * @var StoreLock
     */
    private $storeLock;
    /**
     * @var ShellInterface
     */
    private $shell;
    /**
     * @var PhpExecutableFinderFactory
     */
    private $phpExecutableFinderFactory;
    /**
     * @var string|null
     */
    private $phpPath;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param LiveIndexingSiteProvider $siteProvider
     * @param StoreLock $storeLock
     * @param ShellInterface $shell background shell (see etc/di.xml)
     * @param PhpExecutableFinderFactory $phpExecutableFinderFactory
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        LiveIndexingSiteProvider $siteProvider,
        StoreLock $storeLock,
        ShellInterface $shell,
        PhpExecutableFinderFactory $phpExecutableFinderFactory,
        AthosCommerceLogger $logger
    ) {
        $this->siteProvider = $siteProvider;
        $this->storeLock = $storeLock;
        $this->shell = $shell;
        $this->phpExecutableFinderFactory = $phpExecutableFinderFactory;
        $this->logger = $logger;
    }

    /**
     * Start the command for every store view with live indexing enabled and a site id.
     *
     * @param string $commandName CLI command that accepts --storecodes
     * @param string $lockType StoreLock::TYPE_* the command takes for each store
     * @return string[] store codes a worker was started for
     */
    public function spawn(string $commandName, string $lockType): array
    {
        $spawned = [];
        foreach ($this->siteProvider->getLiveIndexingStores() as $store) {
            $storeCode = $this->getValidStoreCode($store);
            if ($storeCode === null) {
                continue;
            }
            if ($this->storeLock->isLocked($lockType, $storeCode)) {
                $this->logger->info(
                    '[LiveIndexing] Worker not started: previous run still in progress',
                    ['command' => $commandName, 'store' => $storeCode]
                );
                continue;
            }
            try {
                $this->shell->execute(
                    '%s %s %s --storecodes=%s',
                    [$this->getPhpPath(), BP . '/bin/magento', $commandName, $storeCode]
                );
                $spawned[] = $storeCode;
            } catch (\Throwable $exception) {
                $this->logger->error(
                    '[LiveIndexing] Failed to start store worker',
                    [
                        'command' => $commandName,
                        'store' => $storeCode,
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }
        $this->logger->info(
            '[LiveIndexing] Store workers started',
            ['command' => $commandName, 'stores' => $spawned]
        );

        return $spawned;
    }

    /**
     * PHP CLI binary, found as Magento cron does (falls back to "php").
     *
     * @return string
     */
    private function getPhpPath(): string
    {
        if ($this->phpPath === null) {
            $this->phpPath = $this->phpExecutableFinderFactory->create()->find() ?: 'php';
        }

        return $this->phpPath;
    }

    /**
     * Store code safe to pass on the command line, or null.
     *
     * @param StoreInterface $store
     * @return string|null
     */
    private function getValidStoreCode(StoreInterface $store): ?string
    {
        $storeCode = trim((string)$store->getCode());
        if ($storeCode === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $storeCode)) {
            $this->logger->warning('[LiveIndexing] Worker not started: invalid store code', ['store' => $storeCode]);

            return null;
        }

        return $storeCode;
    }
}
