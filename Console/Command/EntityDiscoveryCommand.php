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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Console\Command;

use AthosCommerce\Feed\Api\EntityDiscoveryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use AthosCommerce\Feed\Api\ExecutePendingTasksInterfaceFactory;
use AthosCommerce\Feed\Model\Metric\CollectorInterface;
use AthosCommerce\Feed\Model\Metric\Output\CliOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntityDiscoveryCommand extends Command
{
    const COMMAND_NAME = 'athoscommerce:indexing:entity-discovery';
    const OPTION_STORE_CODES = 'storecodes';

    /**
     * @var EntityDiscoveryInterface
     */
    private $entityDiscovery;
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
     * @param DateTimeFactory $dateTimeFactory
     * @param State $state
     * @param CliOutput $cliOutput
     * @param CollectorInterface $metricCollector
     */
    public function __construct(
        EntityDiscoveryInterface $entityDiscovery,
        DateTimeFactory $dateTimeFactory,
        State $state,
        CliOutput $cliOutput,
        CollectorInterface $metricCollector,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->entityDiscovery = $entityDiscovery;
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
        $this->setName(static::COMMAND_NAME)
            ->setDescription('AthosCommerce: Find products and add them to "athoscommerce_indexing_entity" table so they can be indexed.');

        $this->addOption(
            static::OPTION_STORE_CODES,
            null,
            InputOption::VALUE_OPTIONAL,
            (string)__(
                'Sync Entities only for these stores (optional). Comma separated list '
                . 'e.g. --storecodes=default,french etc',
            ),
        );
        $this->setHelp(
            <<<HELP

Execute sync for all storecodes:
    <comment>%command.full_name%</comment>

Execute sync for a single storecodes:
    <comment>%command.full_name% --storecodes=default</comment>

Execute sync for a multiple storecodes:
    <comment>%command.full_name% --storecodes=default,french</comment>

HELP
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        try {
            $filters = [];
            $storeCodes = $this->getStoreCodes($input);
            if ($storeCodes) {
                $filters[] = __('STORE Codes = %1', implode(', ', $storeCodes));
            }

            $output->writeln('');
            $output->writeln(
                sprintf(
                    '<comment>%s</comment>',
                    __('Begin Sync with filters: %1.', implode(', ', $filters))
                ),
            );
            $output->writeln('--------');

            try {
                $this->state->setAreaCode(Area::AREA_FRONTEND);
            } catch (LocalizedException $e) {
                $output->writeln('<info>Area code is already set.</info>');
                // Ignore "Area code is already set" and continue
            }

            $dateTime = $this->dateTimeFactory->create();
            $output->writeln('<info>Execution started: ' . $dateTime->gmtDate() . '</info>');
            $this->cliOutput->setOutput($output);
            $response = $this->entityDiscovery->execute($storeCodes);
            foreach ($response as  $storeId =>  $storeCode) {
                $output->writeln(
                    '<info>Discovery completed for store code: ' . $storeCode . '</info>'
                );
            }

            $this->metricCollector->setOutput($this->cliOutput);
            $output->writeln('<info>Execution ended: ' . $dateTime->gmtDate() . '</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     *
     * @return string[]
     */
    private function getStoreCodes(InputInterface $input): array
    {
        $codes = $input->getOption(static::OPTION_STORE_CODES);

        return $codes
            ? array_map('trim', explode(',', $codes))
            : [];
    }
}
