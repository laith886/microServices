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

class ProcessOrderJob implements ShouldQueue
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

            $user = User::findOrFail($this->userId);

            // 🟢 ترتيب لمنع Deadlock
            $cartItems = $user->cart_items()->orderBy('product_id')->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception("Cart is empty");
            }

            // 🟢 جلب المنتجات مع lock  
            $productIds = $cartItems->pluck('product_id');

            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalPrice = 0;

            foreach ($cartItems as $item) {

                // 🟢 Edge case check
                if (!isset($products[$item->product_id])) {
                    throw new \Exception("Product not found: " . $item->product_id);
                }

                $product = $products[$item->product_id];

                if ($product->stock < $item->quantity) {
                    throw new \Exception("Not enough stock for product ID: " . $product->id);
                }

                // 🟢 تعديل المخزون
                $product->stock -= $item->quantity;

                // 🟢 السعر من DB (مهم جداً)
                $totalPrice += $product->price * $item->quantity;
            }

            // 🟢 حفظ المنتجات
            foreach ($products as $product) {
                $product->save();
            }

            // 🟢 إنشاء الطلب
            $order = Order::create([
                'user_id' => $user->id,
                'state' => 'pending',
                'total_price' => $totalPrice,
                'address' => $user->location ?? 'Default Address',
            ]);

            // 🟢 إنشاء order items (batch insert)
            $orderItems = [];

            foreach ($cartItems as $item) {
                $product = $products[$item->product_id];

                $orderItems[] = [
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                    'quantity' => $item->quantity,
                    'price' => $product->price, // 🟢 من DB
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            OrderItems::insert($orderItems);

            // 🟢 حذف السلة
            CartItems::where('user_id', $user->id)->delete();
        });
    }
}