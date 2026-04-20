<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
       public function index(){
       return Store::all();
    }

    public function create()
    {

    }

    public function store(Request $request)
    {



    }

    public function show(Store $store)
    {

    }

    public function edit(Store $store)
    {

    }

    public function update(Request $request, Store $store)
    {

    }

    public function destroy(Store $store)
    {

    }


    public function GetStoreProducts($StoreID){

        $store=Store::findorFail($StoreID);
        return $store->products;

    }


}
