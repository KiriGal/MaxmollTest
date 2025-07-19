<?php

namespace App\Http\Controllers;

use app\Enums\OrderStatus;
use App\Http\Requests\OrderFilterRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function index(OrderFilterRequest $request, OrderService $orderService) {
        $orders = $orderService->getPaginatedOrders($request->validated());
        return response()->json(['Заказы' => $orders], 201);
    }

    public function store(StoreOrderRequest $request, OrderService $orderService) {
        $order = $orderService->create($request->validated());
        return response()->json(['message' => 'Заказ создан', 'order_id' => $order->id], 201);
    }

    public function update(UpdateOrderRequest $request, Order $order, OrderService $service)
    {
        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled])) {
            return response()->json(['error' => 'Нельзя обновить завершённый или отменённый заказ'], 400);
        }

        $updatedOrder = $service->update($order, $request->validated());

        return response()->json(['message' => 'Заказ обновлён', 'order_id' => $updatedOrder->id]);
    }

    public function complete(Order $order, OrderService $service)
    {
        $service->complete($order);
        return response()->json(['message' => 'Заказ завершён']);
    }

    public function cancel(Order $order, OrderService $service)
    {
        $service->cancel($order);
        return response()->json(['message' => 'Заказ отменён']);
    }

    public function resume(Order $order, OrderService $service)
    {
        $service->resume($order);
        return response()->json(['message' => 'Заказ возобновлён']);
    }

}
