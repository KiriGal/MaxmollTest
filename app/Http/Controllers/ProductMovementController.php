<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductMovementFilterRequest;
use App\Models\ProductMovement;
use App\Services\ProductMovementService;
use Illuminate\Http\Request;

class ProductMovementController extends Controller
{
    public function index(ProductMovementFilterRequest $request, ProductMovementService $productMovementService){
        $productMovements = $productMovementService->getFilteredMovements($request->validated());
        return response()->json(['Движения продуктов' => $productMovements]);
    }
}
