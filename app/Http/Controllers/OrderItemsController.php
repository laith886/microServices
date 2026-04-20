<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Product;
use App\Jobs\ProcessOrderJob;
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

    ProcessOrderJob::dispatch($user->id);

    return response()->json([
        'message' => 'Order is being processed',
    ], 202);
}








}
