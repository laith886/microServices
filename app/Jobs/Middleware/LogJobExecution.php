<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Log;
use Throwable;

class LogJobExecution
{
    public function handle(object $job, callable $next): void
    {
        $jobName = $job::class;
        $startedAt = microtime(true);

        Log::info('JOB STARTED', [
            'job' => $jobName,
        ]);

        try {
            $next($job);

            Log::info('JOB FINISHED', [
                'job' => $jobName,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ]);
        } catch (Throwable $e) {
            Log::error('JOB FAILED', [
                'job' => $jobName,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ]);

            throw $e;
        }
    }
}
