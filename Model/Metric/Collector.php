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

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use AthosCommerce\Feed\Api\AppConfigInterface;
use AthosCommerce\Feed\Model\Metric\View\FormatterInterface;
use Throwable;

class Collector implements CollectorInterface
{
    /**
     * @var array
     */
    private $metricProviders;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var FormatterInterface
     */
    private $formatter;
    /**
     * @var AppConfigInterface
     */
    private $appConfig;
    /**
     * @var ManagerInterface
     */
    private $eventManager;
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;
    /**
     * @var bool
     */
    private $forceAllowed;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $metrics = [];
    /**
     * @var array
     */
    private $metricsFromLastPrint = [];

    /**
     * Collector constructor.
     * @param OutputInterface $output
     * @param FormatterInterface $formatter
     * @param AppConfigInterface $appConfig
     * @param ManagerInterface $eventManager
     * @param DataObjectFactory $dataObjectFactory
     * @param LoggerInterface $logger
     * @param bool $forceAllowed
     * @param array $metricProviders
     */
    public function __construct(
        OutputInterface $output,
        FormatterInterface $formatter,
        AppConfigInterface $appConfig,
        ManagerInterface $eventManager,
        DataObjectFactory $dataObjectFactory,
        LoggerInterface $logger,
        bool $forceAllowed = false,
        array $metricProviders = []
    ) {
        $this->metricProviders = $metricProviders;
        $this->output = $output;
        $this->formatter = $formatter;
        $this->appConfig = $appConfig;
        $this->eventManager = $eventManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->forceAllowed = $forceAllowed;
        $this->logger = $logger;
    }

    /**
     * @param string $code
     * @param string|null $title
     * @param array $additionalData
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function collect(string $code, ?string $title = null, array $additionalData = []): void
    {
        if (!$this->isAllowed($code)) {
            return;
        }

        $providers = $this->metricProviders[$code] ?? [];
        $pastMetrics = $this->metrics[$code] ?? [];
        $data = $additionalData;
        foreach ($providers as $metricProvider) {
            /** @var MetricProviderInterface $metricProvider */
            try {
                $data = array_merge($data, $metricProvider->getMetrics($data, $pastMetrics));
            } catch (Throwable $exception) {
                $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            }
        }

        $data['__title__'] = $title;
        $this->metrics[$code][] = $data;
        $this->metricsFromLastPrint[$code][] = $data;
    }

    /**
     * @param string $code
     * @param string $printType
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function print(string $code, string $printType = self::PRINT_TYPE_FROM_PREVIOUS): void
    {
        if (!$this->isAllowed($code)) {
            return;
        }

        $metricsToPrint = $printType === self::PRINT_TYPE_FULL
            ? $this->metrics[$code] ?? []
            : $this->metricsFromLastPrint[$code] ?? [];
        $metricsToPrint['__print_type__'] = $printType;
        try {
            $formattedMetric = $this->formatter->format($metricsToPrint, $code);
            $this->output->print($formattedMetric);
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
        }

        if ($printType = self::PRINT_TYPE_FROM_PREVIOUS) {
            $this->metricsFromLastPrint[$code] = [];
        }
    }

    /**
     * @param string $code
     */
    public function reset(string $code): void
    {
        $this->metrics[$code] = [];
        $this->metricsFromLastPrint[$code] = [];
        $providers = $this->metricProviders[$code] ?? [];
        /** @var MetricProviderInterface $provider */
        foreach ($providers as $provider) {
            $provider->reset();
        }
    }

    /**
     *
     */
    public function resetAll(): void
    {
        $this->metrics = [];
        $this->metricsFromLastPrint = [];
        foreach ($this->metricProviders as $metricProviders) {
            /** @var MetricProviderInterface $provider */
            foreach ($metricProviders as $provider) {
                $provider->reset();
            }
        }
    }

    /**
     * @param string $code
     * @return array
     */
    public function getMetrics(string $code): array
    {
        return $this->metrics[$code] ?? [];
    }

    /**
     * @return array
     */
    public function getAllMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @param FormatterInterface $formatter
     */
    public function setFormatter(FormatterInterface $formatter): void
    {
        $this->formatter = $formatter;
    }

    /**
     * @param string $code
     * @return bool
     * @throws FileSystemException
     * @throws RuntimeException
     */
    private function isAllowed(string $code) : bool
    {
        $result = $this->appConfig->isDebug();
        $container = $this->dataObjectFactory->create(
            ['data' => ['collector' => $this, 'result' => $result, 'code' => $code, 'force' => $this->forceAllowed]]
        );

        $this->eventManager->dispatch('athoscommerce_feed_is_metric_allowed', ['container' => $container]);
        return (bool) $container->getResult();
    }
}
