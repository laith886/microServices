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

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $orderId
    ) {}

    public function middleware(): array
    {
        return [
            new LogJobExecution(),
        ];
    }

    public function handle(): void
    {
        Log::info('Generating invoice...', [
            'order_id' => $this->orderId,
        ]);

        sleep(2);

        $order = DB::table('orders')
            ->where('id', $this->orderId)
            ->first();

        if (!$order) {
            throw new \Exception("Order {$this->orderId} not found.");
        }

        $subtotal = DB::table('order_items')
            ->where('order_id', $this->orderId)
            ->selectRaw('SUM(quantity * price) as subtotal')
            ->value('subtotal');

        $subtotal = (float) ($subtotal ?? 0);

        $tax = 0;
        $total = $subtotal + $tax;

        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad($this->orderId, 6, '0', STR_PAD_LEFT);

        DB::table('invoices')->updateOrInsert(
            [
                'order_id' => $this->orderId,
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

        Log::info('Invoice generated successfully.', [
            'order_id' => $this->orderId,
            'invoice_number' => $invoiceNumber,
            'total' => $total,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('GenerateInvoiceJob permanently failed.', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
