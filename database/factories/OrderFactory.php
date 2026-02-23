<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $items = ['laptop', 'phone', 'tablet', 'monitor', 'keyboard', 'mouse', 'headset'];

        return [
            'customer_name'  => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'item'           => fake()->randomElement($items),
            'quantity'       => fake()->numberBetween(1, 5),
            'status'         => fake()->randomElement(['pending', 'confirmed', 'failed']),
            'total_price'    => fake()->randomFloat(2, 10, 5000),
        ];
    }
}
