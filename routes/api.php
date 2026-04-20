<?php


use App\Http\Controllers\CartItemsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('Login',[UserController::class,'Login']);
Route::post('Register',[UserController::class,'Register']);
Route::post('Logout',[UserController::class,'Logout'])->middleware('auth:sanctum');




Route::get('ShowProducts',[ProductController::class,'index']);
Route::get('ShowProduct/{ProductID}',[ProductController::class,'show_one']);
Route::get('GetStore/{ProductID}',[ProductController::class,'GetStoreFromProduct']);
Route::post('StoreProduct',[ProductController::class,'store']);



Route::get('GetStoreProducts/{StoreID}',[StoreController::class,'GetStoreProducts']);



Route::post('AddToCart',[CartItemsController::class,'AddToCart'])->middleware('auth:sanctum');
Route::get('ShowCartItems',[CartItemsController::class,'ShowCartItems'])->middleware('auth:sanctum');;
Route::put('UpdateCartItem/{CartItemID}',[CartItemsController::class,'UpdateCartItem'])->middleware('auth:sanctum');
Route::delete('DeleteCartItem/{CartItemID}',[CartItemsController::class,'DeleteCartItem'])->middleware('auth:sanctum');
Route::delete('DeleteAllCartItems',[CartItemsController::class,'DeleteAllCartItems'])->middleware('auth:sanctum');



Route::post('PlaceOrder',[OrderItemsController::class,'PlaceOrder'])->middleware('auth:sanctum');


Route::get('GetAllOrders',[OrderController::class,'index'])->middleware('auth:sanctum');


Route::post('Search',[SearchController::class,'Search']);






