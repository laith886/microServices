<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Store;

class TestRaceConditionWithLock extends Command
{
    protected $signature = 'app:test-single-product {workers=6} {stock=1}';
    protected $description = 'Test race condition with locking';

    public function handle()
    {
        $workers = (int) $this->argument('workers');
        $initialStock = (int) $this->argument('stock');

        $this->line("Test with locking - {$workers} workers, {$initialStock} stock");

        // Clean data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('orders')->truncate();
        DB::table('order_items')->truncate();
        DB::table('cart_items')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Setup product
        $store = Store::firstOrCreate(['name' => 'Test Store'], ['description' => 'Test', 'location' => 'Test', 'store_photo' => null]);
        $product = Product::updateOrCreate(['id' => 1], [
            'name' => 'Test Product',
            'description' => 'Test',
            'price' => 100,
            'stock' => $initialStock,
            'version' => 0,
            'product_photo' => 'test.jpg',
            'store_id' => $store->id
        ]);

        $this->info("Product setup: ID {$product->id}, Stock {$product->stock}");

        // Create users
        $userIds = [];
        for ($i = 0; $i < $workers; $i++) {
            $user = User::factory()->create();
            $userIds[] = $user->id;
        }

        $this->info("Created {$workers} users");

        // Dispatch jobs to queue for parallel execution
        $this->line("Dispatching {$workers} jobs to queue with locking...");
        foreach ($userIds as $index => $userId) {
            \App\Jobs\ProcessOrderWithLockJob::dispatch($userId)->onQueue('orders');
            $this->line("  [{$index}] Job dispatched for user {$userId}");
        }

        $this->info("All jobs dispatched to queue!");
        $this->warn("Now run queue workers in separate terminals:");
        $this->line("  php artisan queue:work --queue=orders");
        $this->line("  (Run this command in {$workers} separate terminal tabs)");
        $this->line("");
        $this->line("After all workers finish, run:");
        $this->line("  php artisan app:check-results {$expectedOrders}");

        return Command::SUCCESS;
    }
}
