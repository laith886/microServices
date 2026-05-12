<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Product;
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
        $user=Auth::user();

        $cartItems=$user->cart_items;

        $Order=Order::create([
            'user_id'=>$user->id,
            'state'=>'pending',
            'total_price'=> $this->GetOverAllPrice(),
            'address'=>$user->location,
        ]);

        foreach ($cartItems as $item)
        {

            OrderItems::create([
                'product_id'=>$item->product_id,
                'order_id'=>$Order->id,
                'quantity'=>$item->quantity,
                'price'=>$item->price
            ]);


        }
        CartItems::where('user_id', $user->id)->delete();

        return response()->json([
            'message'=>'Order placed successfully',
            'price'=>$this->GetOverAllPrice(),
            'order items'=>$this->GetOrderItems()
        ],
            200
        );
    }




}
