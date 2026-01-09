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

use Magento\Framework\Exception\NoSuchEntityException as OriginalNoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Api\Data\TaskInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Model\Webapi\ExceptionConverterInterface;
use Throwable;

class GetTaskConvertException
{
    /**
     * @var ExceptionConverterInterface
     */
    private $exceptionConverter;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * GetTaskConvertException constructor.
     * @param ExceptionConverterInterface $exceptionConverter
     * @param AthosCommerceLogger $logger
     */
    /**
     * @param ExceptionConverterInterface $exceptionConverter
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ExceptionConverterInterface $exceptionConverter,
        AthosCommerceLogger         $logger
    )
    {
        $this->exceptionConverter = $exceptionConverter;
        $this->logger = $logger;
    }

    /**
     * @param TaskRepositoryInterface $subject
     * @param callable $proceed
     * @param int $id
     * @return TaskInterface
     * @throws Exception
     */
    public function aroundGet(
        TaskRepositoryInterface $subject,
        callable                $proceed,
        int                     $id
    ): TaskInterface
    {
        try {
            $result = $proceed($id);
        } catch (OriginalNoSuchEntityException $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'trace' => $exception->getTraceAsString()
                ]
            );
            $newException = new NoSuchEntityException(
                $exception->getMessage(),
                NoSuchEntityException::CODE,
                $exception
            );
            $convertedException = $this->exceptionConverter->convert($newException);
            throw $convertedException;
        } catch (Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'trace' => $exception->getTraceAsString()
                ]
            );
            $newException = $this->exceptionConverter->convert($exception);
            throw $newException;
        }

        return $result;
    }
}
