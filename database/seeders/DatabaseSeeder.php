<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Warehouse;
use App\Models\Stock;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $warehouses = Warehouse::factory()->count(3)->create();
        $products = Product::factory()->count(10)->create();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                Stock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'stock' => rand(10, 100),
                ]);
            }
        }

        Order::factory()->count(20)->create();
    }
}
