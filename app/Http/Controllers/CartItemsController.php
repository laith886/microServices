<?php

namespace App\Http\Controllers;

use App\Models\CartItems;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemsController extends Controller
{

    private function findOrCreateCartItem($userId, &$product, $quantity, $updateQuantity = false)
    {
        $cartItem = CartItems::where('user_id', $userId)->where('product_id', $product->id)->first();

        if ($cartItem) {
            if ($updateQuantity) {
                $cartItem->quantity = $quantity;
                //$cartItem->price=$quantity*$product->price;
            } else {
                $cartItem->quantity += $quantity;
            }
        } else {
            $cartItem = new CartItems([
                'user_id' => $userId,
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }

        $cartItem->price = $product->price * $cartItem->quantity;
        $cartItem->save();
        $cartItem->refresh();

        return $cartItem;
    }

    public function AddToCart(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userId = $user->id;

        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found']);
        }

        $cartItem = $this->findOrCreateCartItem($userId, $product, $request->quantity);

        return response()->json(['message' => 'Added to cart successfully', 'cartItem' => $cartItem]);
    }

    public function ShowCartItems(): JsonResponse
    {
        $user = Auth::user();
        $cartItems = $user->cart_items;
        return response()->json($cartItems);
    }

    public function UpdateCartItem(Request $request, $CartItemID): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $newQuantity = $request->input('quantity');

        $cartItem = CartItems::findOrFail($CartItemID);


        $this->findOrCreateCartItem($cartItem->user_id, $cartItem->product, $newQuantity, true);

        $cartItem->refresh();
        $cartItem->makeHidden('product');

        return response()->json(['message' => 'Item updated successfully', 'cartItem' => $cartItem]);
    }

    public function DeleteCartItem($cartItemID): JsonResponse{
        $cartItem=CartItems::find($cartItemID);

        if($cartItem){
            $cartItem->delete();
            return response()->json(['message'=>''],204);
        }
        return response()->json(['message'=>'Item not found '],404);

    }

    public function DeleteAllCartItems(): JsonResponse {

        CartItems::truncate();

        return response()->json(['message' => 'All items deleted successfully'], 200);
    }





}
