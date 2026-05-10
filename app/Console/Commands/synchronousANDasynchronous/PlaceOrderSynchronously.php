<?php

namespace App\Console\Commands\synchronousANDasynchronous;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlaceOrderSynchronously extends Command
{
    protected $signature = 'app:place-order-before
                            {user_id}
                            {product_id=1}
                            {quantity=1}';

    protected $description = 'Place order before improvement: synchronous invoice, notification, and duplicated logging';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $productId = (int) $this->argument('product_id');
        $quantity = (int) $this->argument('quantity');

        $startTime = microtime(true);

        $this->info('Starting BEFORE improvement place order...');

        Log::info('BEFORE: Place order started', [
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ]);

        try {
            $result = DB::transaction(function () use ($userId, $productId, $quantity) {
                Log::info('BEFORE: Transaction started');

                $product = DB::table('products')
                    ->where('id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new \Exception('Product not found.');
                }

                Log::info('BEFORE: Product locked', [
                    'product_id' => $product->id,
                    'current_stock' => $product->stock,
                ]);

                if ($product->stock < $quantity) {
                    throw new \Exception('Not enough stock.');
                }

                $stockBefore = $product->stock;
                $stockAfter = $product->stock - $quantity;
                $unitPrice = (float) $product->price;
                $orderTotal = $unitPrice * $quantity;

                $orderId = DB::table('orders')->insertGetId([
                    'user_id' => $userId,
                    'total_price' => $orderTotal,
                    'state' => 'completed',
                    'address' => 'test address',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('BEFORE: Order created', [
                    'order_id' => $orderId,
                ]);

                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('BEFORE: Order item created', [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                ]);

                DB::table('products')
                    ->where('id', $productId)
                    ->update([
                        'stock' => DB::raw("stock - {$quantity}"),
                        'version' => DB::raw('version + 1'),
                        'updated_at' => now(),
                    ]);

                Log::info('BEFORE: Product stock updated', [
                    'product_id' => $productId,
                    'decreased_by' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);



                $invoice = $this->generateInvoiceSynchronously($orderId);

                $notification = $this->sendNotificationSynchronously($orderId, $userId);

                Log::info('BEFORE: Transaction finished', [
                    'order_id' => $orderId,
                ]);

                return [
                    'order' => [
                        'id' => $orderId,
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'product_name' => $product->name ?? 'Unknown Product',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $orderTotal,
                        'state' => 'completed',
                        'address' => 'test address',
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ],
                    'invoice' => $invoice,
                    'notification' => $notification,
                ];
            });

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->newLine();
            $this->info('Order placed successfully before improvement.');
            $this->line('Execution Mode: Synchronous');
            $this->line("Execution Time: {$duration} ms");

            $this->newLine();
            $this->info('Order Details');
            $this->line('Order ID: ' . $result['order']['id']);
            $this->line('User ID: ' . $result['order']['user_id']);
            $this->line('Product ID: ' . $result['order']['product_id']);
            $this->line('Product Name: ' . $result['order']['product_name']);
            $this->line('Quantity: ' . $result['order']['quantity']);
            $this->line('Unit Price: ' . $result['order']['unit_price']);
            $this->line('Order Total: ' . $result['order']['total_price']);
            $this->line('Order State: ' . $result['order']['state']);
            $this->line('Address: ' . $result['order']['address']);
            $this->line('Stock Before: ' . $result['order']['stock_before']);
            $this->line('Stock After: ' . $result['order']['stock_after']);

            $this->newLine();
            $this->info('Invoice Details');
            $this->line('Invoice ID: ' . $result['invoice']['id']);
            $this->line('Invoice Number: ' . $result['invoice']['invoice_number']);
            $this->line('Subtotal: ' . $result['invoice']['subtotal']);
            $this->line('Tax: ' . $result['invoice']['tax']);
            $this->line('Total: ' . $result['invoice']['total']);
            $this->line('Status: ' . $result['invoice']['status']);
            $this->line('Issued At: ' . $result['invoice']['issued_at']);

            $this->newLine();
            $this->info('Notification Details');
            $this->line('Notification ID: ' . $result['notification']['id']);
            $this->line('Channel: ' . $result['notification']['channel']);
            $this->line('Title: ' . $result['notification']['title']);
            $this->line('Body: ' . $result['notification']['body']);
            $this->line('Status: ' . $result['notification']['status']);
            $this->line('Sent At: ' . $result['notification']['sent_at']);

            $this->newLine();
            $this->warn('Invoice and notification were created synchronously.');
            $this->warn('The user waited until invoice generation and notification sending finished.');

            Log::info('BEFORE: Place order completed', [
                'order_id' => $result['order']['id'],
                'invoice_id' => $result['invoice']['id'],
                'notification_id' => $result['notification']['id'],
                'duration_ms' => $duration,
            ]);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->error('Order failed: ' . $e->getMessage());

            Log::error('BEFORE: Place order failed', [
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            return Command::FAILURE;
        }
    }

    private function generateInvoiceSynchronously(int $orderId): array
    {
        Log::info('BEFORE: Invoice generation started', [
            'order_id' => $orderId,
        ]);





        $subtotal = DB::table('order_items')
            ->where('order_id', $orderId)
            ->selectRaw('SUM(quantity * price) as subtotal')
            ->value('subtotal');

        $subtotal = (float) ($subtotal ?? 0);

        $tax = 0;
        $total = $subtotal + $tax;

        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);

        DB::table('invoices')->updateOrInsert(
            [
                'order_id' => $orderId,
            ],
            [
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'issued',
                'issued_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $invoice = DB::table('invoices')
            ->where('order_id', $orderId)
            ->first();

        Log::info('BEFORE: Invoice generated synchronously', [
            'order_id' => $orderId,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'subtotal' => $invoice->subtotal,
            'tax' => $invoice->tax,
            'total' => $invoice->total,
            'status' => $invoice->status,
        ]);

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'subtotal' => $invoice->subtotal,
            'tax' => $invoice->tax,
            'total' => $invoice->total,
            'status' => $invoice->status,
            'issued_at' => $invoice->issued_at,
        ];
    }

    private function sendNotificationSynchronously(int $orderId, int $userId): array
    {
        Log::info('BEFORE: Notification sending started', [
            'order_id' => $orderId,
            'user_id' => $userId,
        ]);

        /*
          هون انا حطيت ال sleep مشان اعمل متل محاكاة لارسال الايميل لانو مافي انترنت بالمدرج لنجرب ارسال ايميل واقعي
         */

        sleep(2);

        $notificationId = DB::table('notification_logs')->insertGetId([
            'order_id' => $orderId,
            'user_id' => $userId,
            'channel' => 'database',
            'title' => 'Order Confirmation',
            'body' => "Your order #{$orderId} has been placed successfully.",
            'status' => 'sent',
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notification = DB::table('notification_logs')
            ->where('id', $notificationId)
            ->first();

        Log::info('BEFORE: Notification sent synchronously', [
            'order_id' => $orderId,
            'user_id' => $userId,
            'notification_id' => $notification->id,
            'channel' => $notification->channel,
            'status' => $notification->status,
        ]);

        return [
            'id' => $notification->id,
            'channel' => $notification->channel,
            'title' => $notification->title,
            'body' => $notification->body,
            'status' => $notification->status,
            'sent_at' => $notification->sent_at,
        ];
    }
}
