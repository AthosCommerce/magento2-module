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

use Magento\Framework\Exception\LocalizedException;

class IndexingEntitySaveException extends LocalizedException
{
    const CODE = 2000;

    /**
     * IndexingEntitySaveException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        $message = "",
        $code = 0,
        ?Throwable $previous = null
    )
    {
        if (!$message) {
            $message = (string)__('Indexing entity could not be saved');
        }

        parent::__construct($message, $code, $previous);
    }
}
