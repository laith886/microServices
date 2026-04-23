<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;

use App\Models\Product;
use App\Models\Order;

class ProcessOrderWithLockJob implements ShouldQueue
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
        DB::transaction(function () {
            // استخدام Pessimistic Locking (lockForUpdate) للحصول على القفل
            $product = Product::where('id', 1)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return;
            }

            if ($product->stock < 1) {
                return;
            }

            // تحديث الـ stock بشكل ذري
            $affectedRows = Product::where('id', $product->id)
                ->where('version', $product->version)
                ->update([
                    'stock' => DB::raw('stock - 1'),
                    'version' => DB::raw('version + 1'),
                    'updated_at' => now()
                ]);

            // إذا لم يتم تحديث أي صف، معناه حدثت مشكلة (race condition)
            if ($affectedRows === 0) {
                // إعادة محاولة أو تسجيل الخطأ
                return;
            }

            Order::create([
                'user_id' => $this->userId,
                'state' => 'completed',
                'total_price' => $product->price,
                'address' => 'test'
            ]);
        });
    }
}
