<?php

namespace App\Command;

use App\Service\ElastalertSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Elastalert Sync Alerts Command synchronizes alerts from Elasticsearch.
 * 
 * This command retrieves alerts from Elasticsearch indices and stores them
 * in the local database using a dedicated synchronization service.
 * It supports time-based filtering and statistics reporting.
 * 
 * The command provides flexible date range options and can run in
 * statistics-only mode for monitoring without data modification.
 */
#[AsCommand(
    name: 'app:alerts:sync',
    description: 'ElastAlert synchronization with the Alert entity'
)]
class ElastalertSyncAlertsCommand extends Command
{
    /**
     * Elastalert synchronization service for alert processing.
     */
    private ElastalertSyncService $elastalertSyncService;

    /**
     * Command constructor with dependency injection.
     * 
     * @param ElastalertSyncService $elastalertSyncService Service for Elastalert alert synchronization
     */
    public function __construct(ElastalertSyncService $elastalertSyncService)
    {
        parent::__construct();
        $this->elastalertSyncService = $elastalertSyncService;
    }

    /**
     * Configure command options and help documentation.
     * 
     * Sets up since and stats options with comprehensive help text
     * including usage examples for different date formats.
     */
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

    /**
     * Execute the alert synchronization command.
     * 
     * Processes alerts from Elasticsearch based on time criteria and
     * optionally shows statistics without performing actual synchronization.
     * 
     * @param InputInterface $input Command line input options
     * @param OutputInterface $output Command line output interface
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Options:
     * - --since, -s: Time range for alert synchronization (default: "-1 hour")
     *   Supports relative formats ("-1 hour", "-5 minutes") or absolute ("2024-12-03 10:00:00")
     * - --stats: Show statistics only without performing synchronization
     * 
     * Process:
     * 1. Parse and validate the since date option
     * 2. Either show statistics or perform full synchronization
     * 3. Display detailed results including processed, created, and error counts
     * 
     * Exit codes:
     * - Command::SUCCESS (0): Synchronization completed successfully
     * - Command::FAILURE (1): Invalid date format or synchronization errors
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize console style helper and get options
        $io = new SymfonyStyle($input, $output);
        $sinceOption = $input->getOption('since');
        $showStatsOnly = $input->getOption('stats');

        // Parse and validate the since date option
        try {
            if (str_starts_with($sinceOption, '-')) {
                // Handle relative time formats like "-1 hour", "-5 minutes"
                $since = new \DateTime($sinceOption);
            } else {
                // Handle absolute datetime formats like "2024-12-03 10:00:00"
                $since = new \DateTime($sinceOption);
            }
        } catch (\Exception $e) {
            $io->error('Invalid date format: ' . $sinceOption);
            return Command::FAILURE;
        }

        $io->title('Sync ElastAlert alerts');
        $io->info('Time: since ' . $since->format('Y-m-d H:i:s'));

        // Show statistics only if requested
        if ($showStatsOnly) {
            return $this->showStats($io, $since);
        }

        // Perform full synchronization with progress indication
        $io->section('Synchronization in progress...');
        $stats = $this->elastalertSyncService->syncElastalertAlerts($since);

        // Display detailed synchronization results
        $io->section('Synchronization results');
        $io->definitionList(
            ['Processed' => $stats['processed']],
            ['Created alerts' => '<fg=green>' . $stats['created'] . '</>'],
            ['Ignored alerts (already existing)' => '<fg=yellow>' . $stats['skipped'] . '</>'],
            ['Errors' => $stats['errors'] > 0 ? '<fg=red>' . $stats['errors'] . '</>' : $stats['errors']]
        );

        // Handle error scenarios
        if ($stats['errors'] > 0) {
            $io->warning('Errors were encountered. Check the logs for more details.');
            return Command::FAILURE;
        }

        // Provide appropriate success message based on results
        if ($stats['created'] > 0 || $stats['processed'] > 0) {
            $io->success('Synchronization completed successfully!');
        } else {
            $io->info('No new alerts to synchronize.');
        }

        return Command::SUCCESS;
    }

    /**
     * Display alert statistics without performing synchronization.
     * 
     * Shows summary information about alerts in the specified time range,
     * including total count and breakdown by rule name.
     * 
     * @param SymfonyStyle $io Console style helper for formatted output
     * @param \DateTime $since Start date for statistics calculation
     * 
     * @return int Command exit code (SUCCESS or FAILURE)
     * 
     * Output includes:
     * - Total number of synchronized alerts in the time range
     * - Table breakdown showing alerts per rule
     * - Error handling for statistics retrieval failures
     */
    private function showStats(SymfonyStyle $io, \DateTime $since): int
    {
        $io->section('Alert statistics');
        
        try {
            // Retrieve alert statistics from the synchronization service
            $stats = $this->elastalertSyncService->getAlertStats($since);
            
            $io->info('Total synchronized alerts: ' . $stats['total_alerts']);
            
            // Display detailed breakdown by rule if available
            if (!empty($stats['alerts_by_rule'])) {
                $io->section('Alerts by rule');
                $tableData = [];
                foreach ($stats['alerts_by_rule'] as $rule) {
                    $tableData[] = [$rule['rule_title'], $rule['alert_count']];
                }
                
                // Create formatted table showing rule names and alert counts
                $io->table(
                    ['Rule', 'Number of alerts'],
                    $tableData
                );
            } else {
                $io->info('No alerts found for this period.');
            }
            
        } catch (\Exception $e) {
            // Handle any errors during statistics retrieval
            $io->error('Error retrieving statistics: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}