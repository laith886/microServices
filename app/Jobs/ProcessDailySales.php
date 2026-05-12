<?php

namespace App\Jobs;

use App\Jobs\Middleware\LogDailySalesJobExecution;
use App\Models\DailyReport;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDailySales implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;
    public int $tries = 1;

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

        $totalSales = 0;
        $totalOrders = 0;
        $processedChunks = 0;

        Order::query()
            ->select(['id', 'total_price', 'created_at'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('id')
            ->chunkById($this->chunkSize, function ($orders) use (&$totalSales, &$totalOrders, &$processedChunks) {
                $processedChunks++;

                foreach ($orders as $order) {
                    $totalSales += (float) $order->total_price;
                    $totalOrders++;
                }
            });

        DailyReport::updateOrCreate(
            ['report_date' => $day->toDateString()],
            [
                'total_orders' => $totalOrders,
                'total_sales' => $totalSales,
            ]
        );

        Log::info('[Daily Sales Job] Report generated successfully', [
            'date' => $day->toDateString(),
            'total_orders' => $totalOrders,
            'total_sales' => $totalSales,
            'processed_chunks' => $processedChunks,
            'chunk_size' => $this->chunkSize,
        ]);
    }
}
