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

namespace AthosCommerce\Feed\Model\Webapi;

use Magento\Framework\Webapi\Exception;
use AthosCommerce\Feed\Exception\GenericException;
use AthosCommerce\Feed\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Exception\UniqueTaskException;
use AthosCommerce\Feed\Exception\ValidationException;
use Throwable;

class DefaultExceptionConverter implements ExceptionConverterInterface
{
    const DEFAULT_HTTP_CODE = 500;

    const TYPE_VALIDATION = 'validation';
    const TYPE_UNIQUE = 'unique';
    const TYPE_GENERAL = 'general';
    const TYPE_NO_SUCH_ENTITY = 'no_such_entity';
    /**
     * @var array
     */
    private $httpCodesMap = [
        self::TYPE_VALIDATION => 400,
        self::TYPE_UNIQUE => 400,
        self::TYPE_GENERAL => 500,
        self::TYPE_NO_SUCH_ENTITY => 404,
    ];
    /**
     * @var array
     */
    private $typeMap = [
        ValidationException::class => self::TYPE_VALIDATION,
        UniqueTaskException::class => self::TYPE_UNIQUE,
        NoSuchEntityException::class => self::TYPE_NO_SUCH_ENTITY,
        GenericException::class => self::TYPE_GENERAL
    ];
    /**
     * @var int
     */
    private $defaultHttpCode;

    /**
     * DefaultExceptionConverter constructor.
     * @param int $defaultHttpCode
     * @param array $typeMap
     * @param array $httpCodesMap
     */
    public function __construct(
        int $defaultHttpCode = self::DEFAULT_HTTP_CODE,
        array $typeMap = [],
        array $httpCodesMap = []
    ) {
        $this->httpCodesMap = array_merge($this->httpCodesMap, $httpCodesMap);
        $this->typeMap = array_merge($this->typeMap, $typeMap);
        $this->defaultHttpCode = $defaultHttpCode;
    }

    /**
     * @param Throwable $exception
     * @return Exception
     */
    public function convert(Throwable $exception): Exception
    {
        $exceptionType = $this->resolveType($exception);
        $httpCode = $this->httpCodesMap[$exceptionType] ?? $this->defaultHttpCode;
        $code = $exception instanceof GenericException ? $exception->getCode() : GenericException::CODE;
        $newException = new Exception(
            __($exception->getMessage()),
            $code,
            $httpCode
        );

        return $newException;
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    private function resolveType(Throwable $exception) : string
    {
        $classHierarchy = array_merge([get_class($exception)], class_parents($exception));
        $result = null;
        foreach ($classHierarchy as $value) {
            if (isset($this->typeMap[$value])) {
                $result = (string) $this->typeMap[$value];
                break;
            }
        }
        return $result ?? self::TYPE_GENERAL;
    }
}
