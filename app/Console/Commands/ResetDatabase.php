<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDatabase extends Command
{
    protected $signature = 'app:reset-database';
    protected $description = 'Reset database - clear orders, cart items, order items while keeping users and products';

    public function handle()
    {
        $this->info('Resetting database...');

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            DB::table('orders')->truncate();
            DB::table('order_items')->truncate();
            DB::table('cart_items')->truncate();
            DB::table('jobs')->truncate();
            DB::table('failed_jobs')->truncate();

            DB::table('products')->where('id', 1)->update([
                'stock' => 3,
                'version' => 0,
                'updated_at' => now()
            ]);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('Database reset successfully!');
            $this->info('Product #1 reset: stock = 3, version = 0');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
