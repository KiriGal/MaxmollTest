<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(){
        return response()->json(['Продукты' => Product::all()]);
    }

    public function stock(ProductService $productService) {
        return response()->json(['Товары с остатками' => $productService->getProductsWithStock()]);
    }
}
