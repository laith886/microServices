<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index()
    {
        return response()->json(Product::all());
    }


    public function create(Request $request)
    {


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {

        $validatedData=$request->validated();

        $product=Product::create($validatedData);

        return response()->json(['message'=>'Product created successfully',$product],201);
    }

    public function show_one($ID)
    {
        $product=Product::findOrFail($ID);
        return response()->json( $product);
    }

    public function edit(Product $product)
    {
        //
    }


    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }

    public function GetStoreFromProduct($ProductID){

    $store=Product::findOrFail($ProductID)->store;
    return response()->json($store);

    }
}
