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

namespace AthosCommerce\Feed\Plugin\Rest;

use Magento\Framework\Webapi\Exception;
use Psr\Log\LoggerInterface;
use AthosCommerce\Feed\Api\CreateTaskInterface;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Model\Webapi\ExceptionConverterInterface;
use Throwable;

class CreateTaskConvertException
{
    /**
     * @var ExceptionConverterInterface
     */
    private $exceptionConverter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreateTaskConvertException constructor.
     * @param ExceptionConverterInterface $exceptionConverter
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExceptionConverterInterface $exceptionConverter,
        LoggerInterface $logger
    ) {
        $this->exceptionConverter = $exceptionConverter;
        $this->logger = $logger;
    }

    /**
     * @param CreateTaskInterface $subject
     * @param callable $proceed
     * @param string $type
     * @param $payload
     * @return TaskInterface
     * @throws Exception
     */
    public function aroundExecute(CreateTaskInterface $subject, callable $proceed, string $type, $payload) : TaskInterface
    {
        try {
            $result = $proceed($type, $payload);
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $newException = $this->exceptionConverter->convert($exception);
            throw $newException;
        }

        return $result;
    }
}
