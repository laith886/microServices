<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckResults extends Command
{
    protected $signature = 'app:check-results {expected_orders=3}';
    protected $description = 'Check race condition test results';

    public function handle()
    {
        $expectedOrders = (int) $this->argument('expected_orders');

        $product = DB::table('products')->where('id', 1)->first();
        $orderCount = DB::table('orders')->count();
        $jobsCount = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $this->line('Final Results:');
        $this->line("  Final stock: {$product->stock} (expected: 0)");
        $this->line("  Orders count: {$orderCount} (expected: {$expectedOrders})");
        $this->line("  Version: {$product->version}");
        $this->line("  Pending jobs: {$jobsCount}");
        $this->line("  Failed jobs: {$failedJobs}");

        if ($product->stock === 0 && $orderCount === $expectedOrders) {
            $this->info('SUCCESS: Results are correct!');
            return Command::SUCCESS;
        } else {
            $this->error('ERROR: Results are incorrect!');

            if ($product->stock !== 0) {
                $this->line('  Problem: Stock did not reach 0');
            }

            if ($orderCount !== $expectedOrders) {
                $this->line("  Problem: Order count incorrect (expected: {$expectedOrders}, actual: {$orderCount})");
                if ($orderCount > $expectedOrders) {
                    $this->line('    This indicates race condition occurred');
                }
            }
            $this->line('');
            return Command::FAILURE;
        }
    }
}
