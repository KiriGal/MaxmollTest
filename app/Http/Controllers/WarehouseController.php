<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(){
        return response()->json(['Все склады' => Warehouse::all()]);
    }
}
