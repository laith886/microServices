<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\CartItems;

class ProcessOrderWithoutLockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $userId;

    public $tries = 3;
    public $timeout = 30;

    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->onQueue('orders');
    }

    public function handle()
    {
        $product = Product::find(1);

        if (!$product) return;

        // إضافة تأخير أطول لزيادة فرصة الـ race condition
        usleep(rand(500000, 1000000)); // 0.5-1 ثانية

        if ($product->stock < 1) return;

        // تأخير إضافي بعد الفحص وقبل التحديث
        usleep(rand(200000, 500000)); // 0.2-0.5 ثانية إضافية

        $product->stock -= 1;
        $product->save();

        Order::create([
            'user_id' => $this->userId,
            'state' => 'completed',
            'total_price' => $product->price,
            'address' => 'test'
        ]);
    }
}
