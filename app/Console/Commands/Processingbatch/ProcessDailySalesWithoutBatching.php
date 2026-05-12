<?php

namespace App\Console\Commands\Processingbatch;

use App\Models\DailyReport;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProcessDailySalesWithoutBatching extends Command
{
    protected $signature = 'app:process-daily-sales-before
                            {--date= : Date in Y-m-d format}
                            {--generate=0 : Generate many test orders by duplicating one real order}
                            {--only-generate : Generate orders only without processing}';

    protected $description = 'BEFORE: Process daily sales synchronously without chunks or background job';

    public function handle(): int
    {
        if (! Schema::hasTable('orders')) {
            $this->error('Table orders does not exist.');
            return Command::FAILURE;
        }

        if (! Schema::hasColumn('orders', 'total_price')) {
            $this->error('Column orders.total_price does not exist. عدّل اسم العمود بالكود حسب جدولك.');
            return Command::FAILURE;
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : today()->startOfDay();

        $generateCount = (int) $this->option('generate');

        if ($generateCount > 0) {
            $generated = $this->generateOrders($generateCount, $date);

            if (! $generated) {
                return Command::FAILURE;
            }

            if ($this->option('only-generate')) {
                $this->info('Orders generated successfully. Processing skipped.');
                return Command::SUCCESS;
            }
        }

        $this->info('BEFORE improvement: synchronous processing started...');
        $this->warn('This version loads all daily orders into memory using get().');

        gc_collect_cycles();

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        $orders = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalSales = 0;
        $totalOrders = 0;

        foreach ($orders as $order) {
            $totalSales += (float) $order->total_price;
            $totalOrders++;
        }

        DailyReport::updateOrCreate(
            ['report_date' => $date->toDateString()],
            [
                'total_orders' => $totalOrders,
                'total_sales' => $totalSales,
            ]
        );

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $this->info('BEFORE processing finished.');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Date', $date->toDateString()],
                ['Total Orders', number_format($totalOrders)],
                ['Total Sales', number_format($totalSales, 2)],
                ['Execution Time', round(($endTime - $startTime) * 1000, 2) . ' ms'],
                ['Memory Start', $this->formatBytes($startMemory)],
                ['Memory End', $this->formatBytes($endMemory)],
                ['Memory Peak', $this->formatBytes($peakMemory)],
                ['Processing Style', 'Synchronous + get()'],
            ]
        );

        return Command::SUCCESS;
    }

    private function generateOrders(int $count, Carbon $date): bool
    {
        $template = DB::table('orders')->first();

        if (! $template) {
            $this->error('لازم يكون عندك طلب واحد حقيقي بجدول orders أولاً حتى ننسخه ونولد بيانات ضخمة واقعية.');
            $this->line('اعمل طلب واحد من مشروعك أو من command الشراء الموجود عندك، وبعدها أعد تشغيل الأمر.');
            return false;
        }

        $this->info("Generating {$count} test orders for date {$date->toDateString()}...");

        $templateRow = (array) $template;

        unset($templateRow['id']);

        $batch = [];
        $batchSize = 1000;
        $now = now()->toDateTimeString();

        for ($i = 1; $i <= $count; $i++) {
            $row = $templateRow;

            $createdAt = $date
                ->copy()
                ->startOfDay()
                ->addSeconds(random_int(0, 86399))
                ->toDateTimeString();

            if (array_key_exists('total_price', $row)) {
                $row['total_price'] = random_int(1000, 500000) / 100;
            }

            if (array_key_exists('created_at', $row)) {
                $row['created_at'] = $createdAt;
            }

            if (array_key_exists('updated_at', $row)) {
                $row['updated_at'] = $now;
            }

            if (array_key_exists('deleted_at', $row)) {
                $row['deleted_at'] = null;
            }

            if (array_key_exists('status', $row)) {
                $row['status'] = 'completed';
            }

            if (array_key_exists('order_number', $row)) {
                $row['order_number'] = 'ORD-' . now()->format('YmdHis') . '-' . $i . '-' . Str::random(5);
            }

            if (array_key_exists('invoice_number', $row)) {
                $row['invoice_number'] = 'INV-' . now()->format('YmdHis') . '-' . $i . '-' . Str::random(5);
            }

            if (array_key_exists('reference', $row)) {
                $row['reference'] = 'REF-' . now()->format('YmdHis') . '-' . $i . '-' . Str::random(5);
            }

            if (array_key_exists('uuid', $row)) {
                $row['uuid'] = (string) Str::uuid();
            }

            $batch[] = $row;

            if (count($batch) >= $batchSize) {
                DB::table('orders')->insert($batch);
                $batch = [];

                if ($i % 10000 === 0) {
                    $this->line("Inserted {$i} orders...");
                }
            }
        }

        if (! empty($batch)) {
            DB::table('orders')->insert($batch);
        }

        $this->info('Test orders generated successfully.');

        return true;
    }

    private function formatBytes(int $bytes): string
    {
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }
}
