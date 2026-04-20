<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    protected $guarded=['id'];


    public function order(){
        return $this->belongsTo(Order::class);
    }
    public function Product(){
        return $this->belongsTo(Product::class);
    }

}
