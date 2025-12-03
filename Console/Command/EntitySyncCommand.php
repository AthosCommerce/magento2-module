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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EntitySyncCommand extends Command
{
    const COMMAND_NAME = 'athoscommerce:indexing:entity-sync';
    const OPTION_SITE_IDS = 'site-ids';

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
        DateTimeFactory $dateTimeFactory,
        State $state,
        CliOutput $cliOutput,
        CollectorInterface $metricCollector,
        ?string $name = null
    ) {
        parent::__construct($name);
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
            ->setDescription('AthosCommerce: Sync recent product changes in batches for all store views with Athos Commerce.');

        $this->addOption(
            static::OPTION_SITE_IDS,
            null,
            InputOption::VALUE_OPTIONAL,
            (string)__(
                'Sync Entities only for these Site IDs (optional). Comma separated list '
                . 'e.g. --site-id site-id-1,site-id-2',
            ),
        );

        $this->setHelp(
            <<<HELP

Execute sync for all site-ids:
    <comment>%command.full_name%</comment>

Execute sync for a single store/site id:
    <comment>%command.full_name% --site-id</comment>

Execute sync for a multiple stores/site ids:
    <comment>%command.full_name% --site-id,site-id-1</comment>

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
            $siteIds = $this->getSiteIds($input);
            if ($siteIds) {
                $filters[] = __('SITE IDs = %1', implode(', ', $siteIds));
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
    private function getSiteIds(InputInterface $input): array
    {
        $siteIds = $input->getOption(static::OPTION_SITE_IDS);

        return $siteIds
            ? array_map('trim', explode(',', $siteIds))
            : [];
    }
}
