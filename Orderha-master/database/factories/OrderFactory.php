<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'total_price' => fake()->numberBetween(50, 1000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
