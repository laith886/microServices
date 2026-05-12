<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\DailyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDailySales implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $totalSales = 0;
        $totalOrders = 0;

        Order::whereDate('created_at', today())
            ->chunk(100, function ($orders) use (&$totalSales, &$totalOrders) {

                foreach ($orders as $order) {
                    $totalSales += $order->total_price;
                    $totalOrders++;
                }
            });

        DailyReport::create([
            'report_date' => today(),
            'total_orders' => $totalOrders,
            'total_sales' => $totalSales,
        ]);
    }
}
