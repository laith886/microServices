<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->unsignedInteger('price');
            $table->string('product_photo');
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->timestamps();
        });


    }


    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
