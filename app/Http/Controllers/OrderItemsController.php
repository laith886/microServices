<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Product;
use App\Jobs\ProcessOrderWithLockJob;
use App\Jobs\ProcessOrderWithoutLockJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class OrderItemsController extends Controller
{
    private function GetOverAllPrice()
    {
        $user=Auth::user();

        $cartItems=$user->cart_items;

        $totalPrice=0;

        foreach ($cartItems as $item){
            $totalPrice+=$item->price;
        }
        return $totalPrice;
    }

    public function GetOrderItems(){
        return OrderItems::all();
    }







    public function PlaceOrder()
{
    $user = Auth::user();

    try {
        ProcessOrderWithLockJob::dispatch($user->id)->onQueue('orders');

        return response()->json([
            'message' => 'Order placed successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to place order: ' . $e->getMessage(),
        ], 500);
    }
}

    public function PlaceOrderWithoutLock()
    {
        $user = Auth::user();

        try {
            ProcessOrderWithoutLockJob::dispatch($user->id)->onQueue('orders');

            return response()->json([
                'message' => 'Order placed successfully ',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to place order: ' . $e->getMessage(),
            ], 500);
        }
    }








}
