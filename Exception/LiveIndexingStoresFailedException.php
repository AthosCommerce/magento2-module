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

namespace AthosCommerce\Feed\Exception;

use Throwable;

/**
 * One or more store views failed during a live indexing run (discovery or entity sync).
 *
 * Thrown after every store has been attempted, so one failing store does not stop the others,
 * while the caller (CLI command, cron worker) still reports the run as failed.
 */
class LiveIndexingStoresFailedException extends GenericException
{
    public const CODE = 10100;

    /**
     * @var array<string, string> store code => error message
     */
    private $failures;

    /**
     * @param string $job e.g. "Discovery" or "Entity sync"
     * @param array<string, string> $failures store code => error message
     * @param Throwable|null $previous first failure
     */
    public function __construct(string $job, array $failures, ?Throwable $previous = null)
    {
        $this->failures = $failures;
        $details = [];
        foreach ($failures as $storeCode => $message) {
            $details[] = $storeCode . ': ' . $message;
        }

        parent::__construct(
            sprintf('%s failed for %d store(s): %s', $job, count($failures), implode('; ', $details)),
            static::CODE,
            $previous
        );
    }

    /**
     * Failed stores and their error messages.
     *
     * @return array<string, string>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }
}
