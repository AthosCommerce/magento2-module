<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterfaceFactory;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Model\Metric\Output\CliOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecutePendingTasksForStoreCommand extends Command
{
    public const COMMAND_NAME = 'athoscommerce:task:execute-pending-for-store';
    private const OPTION_STORE = 'store';

    /**
     * @var ExecutePendingTasksInterfaceFactory
     */
    private $executePendingTasksFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * @var CliOutput
     */
    private $cliOutput;

    /**
     * @var CollectorInterface
     */
    private $metricCollector;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ExecutePendingTasksInterfaceFactory $executePendingTasksFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param State $state
     * @param CliOutput $cliOutput
     * @param CollectorInterface $metricCollector
     * @param AthosCommerceLogger $logger
     * @param string|null $name
     */
    public function __construct(
        ExecutePendingTasksInterfaceFactory $executePendingTasksFactory,
        DateTimeFactory $dateTimeFactory,
        State $state,
        CliOutput $cliOutput,
        CollectorInterface $metricCollector,
        AthosCommerceLogger $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->executePendingTasksFactory = $executePendingTasksFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->state = $state;
        $this->cliOutput = $cliOutput;
        $this->metricCollector = $metricCollector;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('AthosCommerce: Execute pending tasks for a single store.')
            ->addOption(
                self::OPTION_STORE,
                's',
                InputOption::VALUE_REQUIRED,
                'Store code to execute pending tasks for'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startedAt = microtime(true);
        $dateTime = $this->dateTimeFactory->create();
        $storeCode = $input->getOption(self::OPTION_STORE);
        $storeCode = is_string($storeCode) ? trim($storeCode) : '';

        if ($storeCode === '') {
            $output->writeln('<error>The --store option is required.</error>');
            return Command::INVALID;
        }

        try {
            try {
                $this->state->setAreaCode(Area::AREA_FRONTEND);
            } catch (LocalizedException $exception) {
                $output->writeln('<info>Area code is already set.</info>');
            }

            $output->writeln('<info>Store worker started for "' . $storeCode . '": ' . $dateTime->gmtDate() . '</info>');
            $this->logger->info(
                'CLI store worker started for task execution.',
                [
                    'store' => $storeCode,
                    'executionMode' => ExecutePendingTasksInterface::EXECUTION_MODE_CLI,
                ]
            );

            $this->cliOutput->setOutput($output);
            $this->metricCollector->setOutput($this->cliOutput);

            $result = $this->executePendingTasksFactory->create()->executeForStoreWorker(
                $storeCode,
                ExecutePendingTasksInterface::EXECUTION_MODE_CLI
            );

            if ($result === []) {
                $output->writeln('<info>No pending tasks claimed for store "' . $storeCode . '".</info>');
            } else {
                foreach ($result as $taskId => $status) {
                    $output->writeln(sprintf('<info>Task ID %d: %s</info>', $taskId, $status));
                }
            }

            $this->writeExecutionMetrics($output, $startedAt);
            $this->logger->info(
                'CLI store worker completed for task execution.',
                [
                    'store' => $storeCode,
                    'executionMode' => ExecutePendingTasksInterface::EXECUTION_MODE_CLI,
                    'duration' => round(microtime(true) - $startedAt, 4),
                    'peakMemoryBytes' => memory_get_peak_usage(true),
                ]
            );
            $output->writeln('<info>Store worker ended for "' . $storeCode . '": ' . $dateTime->gmtDate() . '</info>');

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'store' => $storeCode,
                    'trace' => $exception->getTraceAsString(),
                    'executionMode' => ExecutePendingTasksInterface::EXECUTION_MODE_CLI,
                ]
            );
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }

    /**
     * @param OutputInterface $output
     * @param float $startedAt
     * @return void
     */
    private function writeExecutionMetrics(OutputInterface $output, float $startedAt): void
    {
        $duration = round(microtime(true) - $startedAt, 4);
        $peakMemoryInMb = round(memory_get_peak_usage(true) / 1048576, 2);

        $output->writeln(sprintf('<info>Duration: %ss</info>', $duration));
        $output->writeln(sprintf('<info>Peak memory: %s MB</info>', $peakMemoryInMb));
    }
}
