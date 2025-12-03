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

namespace AthosCommerce\Feed\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterfaceFactory;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Model\Metric\Output\CliOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecutePendingTasksCommand extends Command
{
    const COMMAND_NAME = 'athoscommerce:feed:execute-pending-tasks';
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
     * ExecutePendingTasks constructor.
     *
     * @param ExecutePendingTasksInterfaceFactory $executePendingTasksFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param State $state
     * @param CliOutput $cliOutput
     * @param CollectorInterface $metricCollector
     * @param string|null $name
     */
    public function __construct(
        ExecutePendingTasksInterfaceFactory $executePendingTasksFactory,
        DateTimeFactory $dateTimeFactory,
        State $state,
        CliOutput $cliOutput,
        CollectorInterface $metricCollector,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->executePendingTasksFactory = $executePendingTasksFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->state = $state;
        $this->cliOutput = $cliOutput;
        $this->metricCollector = $metricCollector;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('AthosCommerce: Execute Pending Tasks aka full feed generation.');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Area can already be set in some contexts (e.g., when invoked by other CLI flows)
            try {
                $this->state->setAreaCode(Area::AREA_FRONTEND);
            } catch (LocalizedException $e) {
                $output->writeln('<info>Area code is already set.</info>');
                // Ignore "Area code is already set" and continue
            }

            $dateTime = $this->dateTimeFactory->create();
            $output->writeln('<info>Execution started: ' . $dateTime->gmtDate() . '</info>');
            $this->cliOutput->setOutput($output);
            $this->metricCollector->setOutput($this->cliOutput);
            $result = $this->executePendingTasksFactory->create()->execute();
            if ($result === []) {
                $output->writeln('<info>No pending tasks found.</info>');
            } else {
                foreach ($result as $taskId => $status) {
                    $output->writeln(sprintf('<info>Task ID %d: %s</info>', $taskId, $status));
                }
            }

            $output->writeln('<info>Execution ended: ' . $dateTime->gmtDate() . '</info>');

            return Command::SUCCESS; // 0
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE; // 1
        }
    }
}
