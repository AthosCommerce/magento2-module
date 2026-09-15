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

namespace AthosCommerce\Feed\Api;

use AthosCommerce\Feed\Api\Data\CronStatusListInterface;

interface GetCronStatusInterface
{
    /**
     * Get the recent AthosCommerce cron status summary.
     *
     * @param string $jobCode
     * @param string $status
     * @param int $currentPage
     * @param int $pageSize
     *
     * @return \AthosCommerce\Feed\Api\Data\CronStatusListInterface
     */
    public function getList(
        string $jobCode = 'all',
        string $status = '',
        int $currentPage = 1,
        int $pageSize = 3
    ): CronStatusListInterface;
}
