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

namespace AthosCommerce\Feed\Model\Feed;

use Exception;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;

interface StorageInterface
{
    /**
     * @param FeedSpecificationInterface $feedSpecification
     */
    public function initiate(FeedSpecificationInterface $feedSpecification) : void;

    /**
     * @param array $data
     * @param int $id
     */
    public function addData(array $data, int $id) : void;

    /**
     * @param bool $deleteFile
     * @param int $id
     */
    public function commit(int $id, bool $deleteFile = true): void;

    /**
     *
     */
    public function rollback() : void;

    /**
     *
     */
    public function getAdditionalData() : array;

    /**
     * @param string $format
     * @return bool
     */
    public function isSupportedFormat(string $format) : bool;
}
