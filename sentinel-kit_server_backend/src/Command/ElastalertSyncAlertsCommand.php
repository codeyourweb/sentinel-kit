<?php

namespace App\Command;

use App\Service\ElastalertSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:alerts:sync',
    description: 'ElastAlert synchronization with the Alert entity'
)]
class ElastalertSyncAlertsCommand extends Command
{
    private ElastalertSyncService $elastalertSyncService;

    public function __construct(ElastalertSyncService $elastalertSyncService)
    {
        parent::__construct();
        $this->elastalertSyncService = $elastalertSyncService;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'since',
                's',
                InputOption::VALUE_REQUIRED,
                'Synchronize alerts since this date (format: Y-m-d H:i:s or -1 hour, -1 day, etc.)',
                '-1 hour'
            )
            ->addOption(
                'stats',
                null,
                InputOption::VALUE_NONE,
                'Show statistics only without synchronizing'
            )
            ->setHelp('
This command synchronizes ElastAlert alerts with the backend Alert entity.

Examples:
  app:elastalert:sync-alerts --stats
  app:elastalert:sync-alerts --since="-5 minutes"
  app:elastalert:sync-alerts --since "2024-12-03 10:00:00"
  app:elastalert:sync-alerts -s "-1 hour" --stats
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sinceOption = $input->getOption('since');
        $showStatsOnly = $input->getOption('stats');

        try {
            if (str_starts_with($sinceOption, '-')) {
                $since = new \DateTime($sinceOption);
            } else {
                $since = new \DateTime($sinceOption);
            }
        } catch (\Exception $e) {
            $io->error('Invalid date format: ' . $sinceOption);
            return Command::FAILURE;
        }

        $io->title('Sync ElastAlert alerts');
        $io->info('Time: since ' . $since->format('Y-m-d H:i:s'));

        if ($showStatsOnly) {
            return $this->showStats($io, $since);
        }

        $io->section('Synchronization in progress...');
        $stats = $this->elastalertSyncService->syncElastalertAlerts($since);

        $io->section('Synchronization results');
        $io->definitionList(
            ['Processed' => $stats['processed']],
            ['Created alerts' => '<fg=green>' . $stats['created'] . '</>'],
            ['Ignored alerts (already existing)' => '<fg=yellow>' . $stats['skipped'] . '</>'],
            ['Errors' => $stats['errors'] > 0 ? '<fg=red>' . $stats['errors'] . '</>' : $stats['errors']]
        );

        if ($stats['errors'] > 0) {
            $io->warning('Errors were encountered. Check the logs for more details.');
            return Command::FAILURE;
        }

        if ($stats['created'] > 0 || $stats['processed'] > 0) {
            $io->success('Synchronization completed successfully!');
        } else {
            $io->info('No new alerts to synchronize.');
        }

        return Command::SUCCESS;
    }

    private function showStats(SymfonyStyle $io, \DateTime $since): int
    {
        $io->section('Alert statistics');
        
        try {
            $stats = $this->elastalertSyncService->getAlertStats($since);
            
            $io->info('Total synchronized alerts: ' . $stats['total_alerts']);
            
            if (!empty($stats['alerts_by_rule'])) {
                $io->section('Alerts by rule');
                $tableData = [];
                foreach ($stats['alerts_by_rule'] as $rule) {
                    $tableData[] = [$rule['rule_title'], $rule['alert_count']];
                }
                
                $io->table(
                    ['Rule', 'Number of alerts'],
                    $tableData
                );
            } else {
                $io->info('No alerts found for this period.');
            }
            
        } catch (\Exception $e) {
            $io->error('Error retrieving statistics: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}