<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogDailySalesJobExecution
{
    public function handle(object $job, Closure $next): void
    {
        $jobName = get_class($job);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        Log::info('[AOP Daily Sales] Job started', [
            'job' => $jobName,
            'started_at' => now()->toDateTimeString(),
            'memory_start_mb' => round($startMemory / 1024 / 1024, 2),
        ]);

        try {
            $next($job);
        } catch (Throwable $exception) {
            Log::error('[AOP Daily Sales] Job failed', [
                'job' => $jobName,
                'error' => $exception->getMessage(),
                'failed_at' => now()->toDateTimeString(),
            ]);

            throw $exception;
        } finally {
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);

            Log::info('[AOP Daily Sales] Job finished', [
                'job' => $jobName,
                'finished_at' => now()->toDateTimeString(),
                'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
                'memory_end_mb' => round($endMemory / 1024 / 1024, 2),
                'memory_peak_mb' => round($peakMemory / 1024 / 1024, 2),
            ]);
        }
    }
}
