<?php

namespace Euginepj\ApiLogger\Commands;

use Euginepj\ApiLogger\Models\ApiRequestLog;
use Illuminate\Console\Command;

class PruneApiLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-logger:prune {--days= : The number of days of logs to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old API request logs from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('api-logger.prune_days', 30));

        $cutoff = now()->subDays($days);

        $deletedCount = ApiRequestLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Successfully pruned {$deletedCount} API request logs older than {$days} days.");

        return self::SUCCESS;
    }
}
