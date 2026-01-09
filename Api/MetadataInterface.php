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

interface MetadataInterface
{
    public const TASK_STATUS_PENDING = 'pending';
    public const TASK_STATUS_PROCESSING = 'processing';
    public const TASK_STATUS_SUCCESS = 'success';
    public const TASK_STATUS_ERROR = 'error';
    public const FEED_GENERATION_TASK_CODE = 'feed_generation';

    public const FORMAT_JSON = 'json';
    public const FORMAT_GZ = 'gz';
    public const FORMAT_JSON_GZ = 'json.gz';
}
