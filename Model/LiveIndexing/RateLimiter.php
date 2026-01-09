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

use AthosCommerce\Feed\Api\RateLimiterInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class RateLimiter implements RateLimiterInterface
{
    private $perSecond = 8;
    private $perMinute = 450;
    private $secondTokens;
    private $minuteTokens;
    private $secondWindowStart;
    private $minuteWindowStart;
    private $logger;
    private $context;

    /**
     * @param int $perSecond
     * @param int $perMinute
     * @param string $context
     */
    public function __construct(
        ?int $perSecond = null,
        ?int $perMinute = null,
        string $context = ''
    ) {
        $this->perSecond = max(1, $this->perSecond);
        $this->perMinute = max(1, $this->perMinute);

        $this->context = $context;

        $now = microtime(true);
        $this->secondWindowStart = (int)floor($now);
        $this->minuteWindowStart = (int)floor($now / 60);

        $this->secondTokens = $this->perSecond;
        $this->minuteTokens = $this->perMinute;
    }

    /**
     * {@inheritdoc}
     */
    public function waitForAvailableSlot(): void
    {
        while (true) {
            $now = microtime(true);
            $currentSecond = (int)floor($now);
            $currentMinute = (int)floor($now / 60);

            if ($currentSecond !== $this->secondWindowStart) {
                $this->secondWindowStart = $currentSecond;
                $this->secondTokens = $this->perSecond;
            }

            if ($currentMinute !== $this->minuteWindowStart) {
                $this->minuteWindowStart = $currentMinute;
                $this->minuteTokens = $this->perMinute;
            }

            if ($this->secondTokens > 0 && $this->minuteTokens > 0) {
                $this->secondTokens--;
                $this->minuteTokens--;

                return;
            }

            /*$this->logger->debug(
                '[RateLimiter] waiting for slot',
                [
                    'context' => $this->context,
                    'rps' => $this->rps,
                    'rpm' => $this->rpm,
                    'second_tokens' => $this->secondTokens,
                    'minute_tokens' => $this->minuteTokens,
                ]
            );*/

            usleep(100000); // 100ms only for now, may keeps latency low
        }
    }
}
