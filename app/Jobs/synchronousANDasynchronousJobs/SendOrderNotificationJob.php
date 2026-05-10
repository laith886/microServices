<?php

namespace App\Jobs\synchronousANDasynchronousJobs;

use App\Jobs\Middleware\LogJobExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $orderId,
        public int $userId
    ) {}

    public function middleware(): array
    {
        return [
            new LogJobExecution(),
        ];
    }

    public function handle(): void
    {
        Log::info('Sending order notification...', [
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
        ]);

        sleep(1);

        $order = DB::table('orders')
            ->where('id', $this->orderId)
            ->first();

        if (!$order) {
            throw new \Exception("Order {$this->orderId} not found.");
        }

        DB::table('notification_logs')->insert([
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'channel' => 'database',
            'title' => 'Order Confirmation',
            'body' => "Your order #{$this->orderId} has been placed successfully.",
            'status' => 'sent',
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Order notification sent successfully.', [
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendOrderNotificationJob permanently failed.', [
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
