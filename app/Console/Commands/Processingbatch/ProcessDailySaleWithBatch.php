<?php

namespace App\Console\Commands\Processingbatch;

use App\Jobs\ProcessDailySales;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessDailySaleWithBatch extends Command
{
    protected $signature = 'app:process-daily-sales-after
                            {--date= : Date in Y-m-d format}
                            {--chunk=1000 : Number of orders per chunk}
                            {--run-now : Run the job immediately for performance comparison}';

    protected $description = 'AFTER: Process daily sales using background job and chunks';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : today()->startOfDay();

        $chunkSize = (int) $this->option('chunk');

        if ($chunkSize <= 0) {
            $this->error('Chunk size must be greater than zero.');
            return Command::FAILURE;
        }

        $this->info('AFTER improvement started...');
        $this->line('Processing style: Background Job + chunkById + AOP middleware');

        gc_collect_cycles();

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        if ($this->option('run-now')) {
            $this->warn('Demo mode: running the job immediately to compare real processing time.');

            ProcessDailySales::dispatchSync(
                $date->toDateString(),
                $chunkSize
            );
        } else {
            ProcessDailySales::dispatch(
                $date->toDateString(),
                $chunkSize
            )->onQueue('reports');

            $this->info('Job dispatched successfully to reports queue.');
            $this->line('Run this command to process it:');
            $this->line('php artisan queue:work --queue=reports --stop-when-empty');
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Date', $date->toDateString()],
                ['Chunk Size', number_format($chunkSize)],
                ['Command Response Time', round(($endTime - $startTime) * 1000, 2) . ' ms'],
                ['Memory Start', $this->formatBytes($startMemory)],
                ['Memory End', $this->formatBytes($endMemory)],
                ['Memory Peak', $this->formatBytes($peakMemory)],
                ['Processing Style', $this->option('run-now') ? 'Job executed now + chunks' : 'Queued background job + chunks'],
                ['AOP Logs', 'storage/logs/laravel.log'],
            ]
        );

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }
}
