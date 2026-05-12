<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private function SearchForProducts($element){

        return Product::where('name','like', "%{$element}%")->

        orWhere('description', 'like', "%{$element}%")->

        get();
    }
    private function SearchForStores($element){

        return Store::where('name','like', "%{$element}%")->

        orWhere('description', 'like', "%{$element}%")->

        get();
    }

    public function Search(Request $request){

        $element=$request->input('element');
        $type=$request->input('type');

        if($type=='product'){

           $result=$this->SearchForProducts($element);

        }elseif ($type=='store'){

            $result=$this->SearchForStores($element);

        }else{

            return response()->json(['message'=>'invalid error'],404);

        }

        return response()->json($result,200);
    }



}
