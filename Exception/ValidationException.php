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
use Magento\Framework\Phrase;
use Throwable;

class ValidationException extends GenericException
{
    public const CODE = 1000;

    /**
     * @var array
     */
    private $details;

    /**
     * @var LocalizedException[]
     */
    private $errors;

    /**
     * ValidationException constructor.
     * @param array $messages
     * @param int $code
     * @param Throwable|null $previous
     * @param array $details
     */
    public function __construct(
        $messages = [],
        $code = 0,
        ?Throwable $previous = null,
        array $details = []
    ) {
        $message = '';
        foreach ($messages as $error) {
            $message .= $error . PHP_EOL;
        }

        $this->details = $details;
        $this->errors = array_map(
            static function (array $detail): LocalizedException {
                return new LocalizedException(
                    new Phrase($detail['message'] ?? 'Validation error')
                );
            },
            $details
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return LocalizedException[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
