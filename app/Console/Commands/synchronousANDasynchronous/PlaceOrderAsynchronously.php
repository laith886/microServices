<?php

namespace App\Console\Commands\synchronousANDasynchronous;

use App\Jobs\synchronousANDasynchronousJobs\GenerateInvoiceJob;
use App\Jobs\synchronousANDasynchronousJobs\SendOrderNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlaceOrderAsynchronously extends Command
{
    protected $signature = 'app:place-order-after
                            {user_id}
                            {product_id=1}
                            {quantity=1}';

    protected $description = 'Place order after improvement: critical order logic only, slow tasks moved to queues';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $productId = (int) $this->argument('product_id');
        $quantity = (int) $this->argument('quantity');

        $startTime = microtime(true);

        $this->info('Starting AFTER improvement place order...');

        Log::info('AFTER: Place order started', [
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

        try {
            $orderId = DB::transaction(function () use ($userId, $productId, $quantity) {
                $product = DB::table('products')
                    ->where('id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new \Exception('Product not found.');
                }

                if ($product->stock < $quantity) {
                    throw new \Exception('Not enough stock.');
                }

                $orderId = DB::table('orders')->insertGetId([
                    'user_id' => $userId,
                    'total_price' => $product->price * $quantity,
                    'state' => 'completed',
                    'address' => 'test address',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('products')
                    ->where('id', $productId)
                    ->update([
                        'stock' => DB::raw("stock - {$quantity}"),
                        'version' => DB::raw('version + 1'),
                        'updated_at' => now(),
                    ]);



                GenerateInvoiceJob::dispatch($orderId)
                    ->onQueue('invoices')
                    ->afterCommit();

                SendOrderNotificationJob::dispatch($orderId, $userId)
                    ->onQueue('notifications')
                    ->afterCommit();

                return $orderId;
            });

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info('Order placed successfully after improvement.');
            $this->line("Order ID: {$orderId}");
            $this->line("Execution time: {$duration} ms");
            $this->warn('Invoice and notification were dispatched to queue.');

            Log::info('AFTER: Place order completed', [
                'order_id' => $orderId,
                'duration_ms' => $duration,
            ]);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->error('Order failed: ' . $e->getMessage());

            Log::error('AFTER: Place order failed', [
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return Command::FAILURE;
        }
    }
}
