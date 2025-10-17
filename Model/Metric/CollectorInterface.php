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

namespace AthosCommerce\Feed\Model\Metric;

use AthosCommerce\Feed\Model\Metric\View\FormatterInterface;

/**
 * Interface CollectorInterface
 * @package AthosCommerce\Feed\Model\Metric
 */
interface CollectorInterface
{
    /**
     *
     */
    const CODE_PRODUCT_FEED = 'product_feed';
    /**
     *
     */
    const PRINT_TYPE_FROM_PREVIOUS = 'from_previous';
    /**
     *
     */
    const PRINT_TYPE_FULL = 'full';

    /**
     * @param string $code
     * @param string|null $title
     * @param array $additionalData
     */
    public function collect(string $code, ?string $title = null, array $additionalData = []) : void;

    /**
     * @param string $code
     * @param string $printType
     */
    public function print(string $code, string $printType = self::PRINT_TYPE_FROM_PREVIOUS) : void;

    /**
     * @param string|null $code
     */
    public function reset(string $code) : void;

    /**
     *
     */
    public function resetAll() : void;

    /**
     * @param string $code
     * @return array
     */
    public function getMetrics(string $code) : array;

    /**
     * @return array
     */
    public function getAllMetrics() : array;

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output) : void;

    /**
     * @param FormatterInterface $formatter
     */
    public function setFormatter(FormatterInterface $formatter) : void;
}
