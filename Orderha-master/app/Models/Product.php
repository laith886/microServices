<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded=['id'];


    public function cart_items(){
        return $this->hasMany(CartItems::class);
    }
    public function categories(){
        return $this->hasMany(Category::class);
    }

    public function order_item(){
        return $this->hasMany(OrderItems::class);
    }

    public function store(){
        return $this->belongsTo(Store::class);
    }
}
