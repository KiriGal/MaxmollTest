<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer' => $this->faker->name(),
            'created_at' => now()->subDays(rand(0, 30)),
            'completed_at' => rand(0, 1) ? now() : null,
            'warehouse_id' => Warehouse::factory(),
            'status' => collect([
                OrderStatus::Active->value,
                OrderStatus::Completed->value,
                OrderStatus::Cancelled->value,
            ])->random(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Order $order) {
            $products = Product::inRandomOrder()->take(rand(1, 5))->get();

            foreach ($products as $product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'count' => rand(1, 10),
                ]);
            }
        });
    }
}
