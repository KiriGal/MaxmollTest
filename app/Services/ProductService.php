<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    /**
     * Получить список товаров c остатками по каждому складу.
     *
     * Для каждого товара возвращается массив:
     *  [
     *      'id'      => int,
     *      'name'    => string,
     *      'price'   => float|int|string,   // зависит от типа колонки в БД
     *      'stocks'  => Collection<array{
     *                         warehouse:string,
     *                         stock:int
     *                     }>
     *  ]
     *
     * @return Collection<int,array{
     *     id:int,
     *     name:string,
     *     price:mixed,
     *     stocks:Collection<int,array{warehouse:string,stock:int}>
     * }>
     */
    public function getProductsWithStock()
    {
        return Product::with(['stocks.warehouse'])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stocks' => $product->stocks->map(function ($stock) {
                        return [
                            'warehouse' => $stock->warehouse->name,
                            'stock' => $stock->stock,
                        ];
                    }),
                ];
            });
    }
}
