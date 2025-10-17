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

namespace AthosCommerce\Feed\Model\Metric\View;

use AthosCommerce\Feed\Model\Metric\CollectorInterface;

class DefaultFormatter implements FormatterInterface
{
    /**
     * @var SortOrderProvider
     */
    private $sortOrderProvider;

    /**
     * DefaultFormatter constructor.
     * @param SortOrderProvider $sortOrderProvider
     */
    public function __construct(
        SortOrderProvider $sortOrderProvider
    ) {
        $this->sortOrderProvider = $sortOrderProvider;
    }

    /**
     * @param array $data
     * @param string $code
     * @return string
     */
    public function format(array $data, string $code): string
    {
        $printType = $data['__print_type__'] ?? CollectorInterface::PRINT_TYPE_FULL;
        unset($data['__print_type__']);
        $metricsView = [];
        $blockedKeys = [];
        $keys = [];
        foreach ($data as $metrics) {
            foreach ($metrics as $key => $value) {
                if (!in_array($key, $keys)) {
                    $keys[] = $key;
                }
            }
        }

        foreach ($data as $metrics) {
            foreach ($keys as $key) {
                if (in_array($key, $blockedKeys)) {
                    continue;
                }

                $value = $metrics[$key] ?? '-';
                if (is_array($value)) {
                    $isStatic = $value['static'] ?? false;
                    if ($isStatic) {
                        $blockedKeys[] = $key;
                    }

                    $value = $value['value'] ?? '';
                }

                $metricsView[$key][] = $value;
            }
        }

        $metricsView = $this->sortData($metricsView, $code);
        if ($printType === CollectorInterface::PRINT_TYPE_FULL) {
            $header = '----- Final Result -----';
        } elseif (isset($metricsView['__title__'])) {
            $header = '----- ' . implode(' -> ', $metricsView['__title__']) . ' -----';
            unset($metricsView['__title__']);
        } else {
            $header = '----- Undefined Partial Result -----';
        }

        $result = $header . PHP_EOL;

        foreach ($metricsView as $key => $item) {
            $valueStr = implode(' -> ', $item);
            $result .= $key . ": " . $valueStr . PHP_EOL;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param string $code
     * @return array
     */
    private function sortData(array $data, string $code) : array
    {
        $sortOrder = $this->sortOrderProvider->getSortOrder($code);
        asort($sortOrder);
        $notSortedKeys = array_diff_key($data, $sortOrder);
        foreach ($notSortedKeys as &$value) {
            $value = 9999;
        }

        $sortedKeys = array_merge($sortOrder, $notSortedKeys);
        $result = [];
        foreach ($sortedKeys as $key => $position) {
            if (isset($data[$key])) {
                $result[$key] = $data[$key];
            }
        }

        return $result;
    }
}
