<?php

namespace App\Console\Commands\synchronousANDasynchronous;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOrderAsyncResult extends Command
{
    protected $signature = 'app:check-order-async-result {order_id}';

    protected $description = 'Check invoice and notification result for an order';

    public function handle(): int
    {
        $orderId = (int) $this->argument('order_id');

        $order = DB::table('orders')
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            $this->error("Order #{$orderId} not found.");
            return Command::FAILURE;
        }

        $this->info("Order Found");
        $this->line("Order ID: {$order->id}");
        $this->line("User ID: {$order->user_id}");

        if (isset($order->total_price)) {
            $this->line("Order Total: {$order->total_price}");
        }

        if (isset($order->status)) {
            $this->line("Order Status: {$order->status}");
        }

        $this->newLine();

        $invoice = DB::table('invoices')
            ->where('order_id', $orderId)
            ->first();

        if ($invoice) {
            $this->info("Invoice Issued");
            $this->line("Invoice Number: {$invoice->invoice_number}");
            $this->line("Subtotal: {$invoice->subtotal}");
            $this->line("Tax: {$invoice->tax}");
            $this->line("Total: {$invoice->total}");
            $this->line("Status: {$invoice->status}");
            $this->line("Issued At: {$invoice->issued_at}");
        } else {
            $this->warn("Invoice not issued yet.");
        }

        $this->newLine();

        $notifications = DB::table('notification_logs')
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->get();

        if ($notifications->isEmpty()) {
            $this->warn("No notification was sent yet.");
        } else {
            $this->info("Notifications Sent");

            foreach ($notifications as $notification) {
                $this->line("--------------------------------");
                $this->line("Notification ID: {$notification->id}");
                $this->line("User ID: {$notification->user_id}");
                $this->line("Channel: {$notification->channel}");
                $this->line("Title: {$notification->title}");
                $this->line("Body: {$notification->body}");
                $this->line("Status: {$notification->status}");
                $this->line("Sent At: {$notification->sent_at}");
            }
        }

        return Command::SUCCESS;
    }
}
