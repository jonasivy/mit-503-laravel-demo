<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Seed 5 sample orders for demo purposes.
     * These will be visible immediately via GET /api/v1/orders.
     */
    public function run(): void
    {
        $orders = [
            [
                'customer_name'  => 'Alice Johnson',
                'customer_email' => 'alice@example.com',
                'item'           => 'laptop',
                'quantity'        => 1,
                'status'         => 'confirmed',
                'total_price'    => 1299.99,
            ],
            [
                'customer_name'  => 'Bob Smith',
                'customer_email' => 'bob@example.com',
                'item'           => 'phone',
                'quantity'        => 2,
                'status'         => 'pending',
                'total_price'    => 1599.98,
            ],
            [
                'customer_name'  => 'Carol Davis',
                'customer_email' => 'carol@example.com',
                'item'           => 'tablet',
                'quantity'        => 1,
                'status'         => 'confirmed',
                'total_price'    => 499.99,
            ],
            [
                'customer_name'  => 'David Wilson',
                'customer_email' => 'david@example.com',
                'item'           => 'monitor',
                'quantity'        => 3,
                'status'         => 'failed',
                'total_price'    => 899.97,
            ],
            [
                'customer_name'  => 'Eva Martinez',
                'customer_email' => 'eva@example.com',
                'item'           => 'keyboard',
                'quantity'        => 5,
                'status'         => 'pending',
                'total_price'    => 374.95,
            ],
        ];

        foreach ($orders as $order) {
            Order::create($order);
        }
    }
}
