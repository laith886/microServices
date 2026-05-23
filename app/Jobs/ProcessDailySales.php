<?php

namespace App\Jobs;

use App\Jobs\Middleware\LogDailySalesJobExecution;
use App\Models\DailyReport;
use App\Models\DailySalesProcessingProgress;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDailySales implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    // بدل محاولة واحدة، نخليه يحاول أكثر من مرة
    public int $tries = 3;

    public function __construct(
        public string $date,
        public int $chunkSize = 1000
    ) {
    }

    public function middleware(): array
    {
        return [
            new LogDailySalesJobExecution(),
        ];
    }

    public function handle(): void
    {
        $day = Carbon::parse($this->date);

        $startDate = $day->copy()->startOfDay();
        $endDate = $day->copy()->endOfDay();

        $progress = DailySalesProcessingProgress::firstOrCreate(
            ['report_date' => $day->toDateString()],
            [
                'status' => 'pending',
                'last_processed_id' => 0,
                'total_orders' => 0,
                'total_sales' => 0,
                'processed_chunks' => 0,
                'chunk_size' => $this->chunkSize,
            ]
        );

        if ($progress->status === 'completed') {
            Log::info('[Daily Sales Job] Job already completed, skipping.', [
                'date' => $day->toDateString(),
            ]);

            return;
        }

        $progress->update([
            'status' => 'processing',
            'chunk_size' => $this->chunkSize,
            'started_at' => $progress->started_at ?? now(),
        ]);

        $totalSales = (float) $progress->total_sales;
        $totalOrders = (int) $progress->total_orders;
        $processedChunks = (int) $progress->processed_chunks;
        $lastProcessedId = (int) $progress->last_processed_id;

        Order::query()
            ->select(['id', 'total_price', 'created_at'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('id', '>', $lastProcessedId)
            ->chunkById($this->chunkSize, function ($orders) use (
                &$totalSales,
                &$totalOrders,
                &$processedChunks,
                $progress
            ) {
                $chunkSales = 0;
                $chunkOrders = 0;
                $lastIdInChunk = 0;

                foreach ($orders as $order) {
                    $chunkSales += (float) $order->total_price;
                    $chunkOrders++;
                    $lastIdInChunk = $order->id;
                }

                $totalSales += $chunkSales;
                $totalOrders += $chunkOrders;
                $processedChunks++;

                $progress->update([
                    'last_processed_id' => $lastIdInChunk,
                    'total_orders' => $totalOrders,
                    'total_sales' => $totalSales,
                    'processed_chunks' => $processedChunks,
                    'status' => 'processing',
                ]);

                Log::info('[Daily Sales Job] Chunk processed and progress saved', [
                    'date' => $progress->report_date->toDateString(),
                    'last_processed_id' => $lastIdInChunk,
                    'total_orders_so_far' => $totalOrders,
                    'total_sales_so_far' => $totalSales,
                    'processed_chunks' => $processedChunks,
                ]);
            });

        DailyReport::updateOrCreate(
            ['report_date' => $day->toDateString()],
            [
                'total_orders' => $totalOrders,
                'total_sales' => $totalSales,
            ]
        );

        $progress->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);

        Log::info('[Daily Sales Job] Report generated successfully', [
            'date' => $day->toDateString(),
            'total_orders' => $totalOrders,
            'total_sales' => $totalSales,
            'processed_chunks' => $processedChunks,
            'chunk_size' => $this->chunkSize,
            'last_processed_id' => $progress->last_processed_id,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $day = Carbon::parse($this->date);

        DailySalesProcessingProgress::where('report_date', $day->toDateString())
            ->update([
                'status' => 'failed',
            ]);

        Log::error('[Daily Sales Job] Job failed permanently', [
            'date' => $day->toDateString(),
            'error' => $exception->getMessage(),
        ]);
    }
}
