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

namespace AthosCommerce\Feed\Model\Metric\MetricProvider;

use AthosCommerce\Feed\Model\Metric\MetricProviderInterface;

class MemoryProvider implements MetricProviderInterface
{
    /**
     * @param array $currentMetrics
     * @param array $previousMetrics
     * @return array
     */
    public function getMetrics(array $currentMetrics, array $previousMetrics): array
    {
        $result = [
            'usage' => round(memory_get_usage() / 1024 / 1024, 4),
            'usage_real' => round(memory_get_usage(true) / 1024 / 1024, 4),
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 4),
            'peak_real' => round(memory_get_peak_usage(true) / 1024 / 1024, 4),
        ];

        $previousMetric = array_pop($previousMetrics);
        if (empty($previousMetric)) {
            return $result;
        }

        foreach ($result as $key => $value) {
            if (array_key_exists($key, $previousMetric)) {
                $diff = $value - $previousMetric[$key];
                $diffString = $diff > 0 ? '+' : '-';
                $diffString .= round(abs($diff), 4);
                $result[$key . '_diff'] = $diffString;
            }
        }

        return $result;
    }

    /**
     *
     */
    public function reset(): void
    {
        //do nothing
    }
}
