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

/**
 * Persists failures of the live indexing CLI commands in the module log.
 *
 * Cron runs the commands as detached store workers whose console output goes to /dev/null, so a
 * failure written only to the console would be lost. Caught exceptions are logged here, and a
 * shutdown handler logs PHP fatal errors (memory limit, timeouts) that no catch block sees.
 */
class WorkerFailureLogger
{
    private const FATAL_ERROR_TYPES = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    /**
     * Memory set aside while a command runs and freed in the shutdown handler, so a fatal caused by
     * the memory limit can still be logged.
     */
    private const RESERVED_MEMORY_BYTES = 262144;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var array{command: string, store_codes: string[]}|null
     */
    private $running;
    /**
     * @var bool
     */
    private $shutdownHandlerRegistered = false;
    /**
     * @var string|null
     */
    private $reservedMemory;

    /**
     * @param AthosCommerceLogger $logger
     */
    public function __construct(AthosCommerceLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Start watching a command run for fatal errors.
     *
     * @param string $commandName
     * @param string[] $storeCodes empty for all stores
     * @return void
     */
    public function start(string $commandName, array $storeCodes): void
    {
        $this->running = ['command' => $commandName, 'store_codes' => array_values($storeCodes)];
        $this->reservedMemory = str_repeat(' ', self::RESERVED_MEMORY_BYTES);
        if (!$this->shutdownHandlerRegistered) {
            $this->shutdownHandlerRegistered = true;
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            register_shutdown_function([$this, 'logFatalError']);
        }
    }

    /**
     * Stop watching: the command finished (successfully or with a logged exception).
     *
     * @return void
     */
    public function finish(): void
    {
        $this->running = null;
        $this->reservedMemory = null;
    }

    /**
     * Log an exception that ended the command.
     *
     * @param string $commandName
     * @param string[] $storeCodes
     * @param \Throwable $exception
     * @return void
     */
    public function logException(string $commandName, array $storeCodes, \Throwable $exception): void
    {
        $this->logger->error(
            sprintf('[LiveIndexing] %s failed: %s', $commandName, $exception->getMessage()),
            [
                'command' => $commandName,
                'store_codes' => array_values($storeCodes),
                'exception' => get_class($exception),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }

    /**
     * Shutdown handler: log a fatal error that stopped a command that was still running.
     *
     * @return void
     */
    public function logFatalError(): void
    {
        $this->reservedMemory = null;
        if ($this->running === null) {
            return;
        }
        $error = error_get_last();
        if (!is_array($error) || !in_array($error['type'] ?? null, self::FATAL_ERROR_TYPES, true)) {
            return;
        }
        $this->logger->critical(
            sprintf('[LiveIndexing] %s stopped by a fatal error: %s', $this->running['command'], $error['message']),
            [
                'command' => $this->running['command'],
                'store_codes' => $this->running['store_codes'],
                'file' => $error['file'] ?? '',
                'line' => $error['line'] ?? 0,
            ]
        );
        $this->running = null;
    }
}
