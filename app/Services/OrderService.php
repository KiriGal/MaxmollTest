<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductMovement;
use App\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Вернуть постраничный список заказов с учётом фильтров из запроса.
     *
     * Поддерживаемые query-параметры:
     *  - status      int|string   — значение enum `OrderStatus`
     *  - customer    string       — подстрочный поиск по колонке `customer`
     *  - date_from   Y-m-d        — нижняя граница даты создания (включительно)
     *  - date_to     Y-m-d        — верхняя граница даты создания (включительно)
     *  - per_page    int          — размер страницы (по-умолчанию 15)
     *
     * @param  Request|object                    $request
     * @return LengthAwarePaginator<Order>
     */
    public function getPaginatedOrders(Request $request): LengthAwarePaginator
    {
        $query = Order::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer')) {
            $query->where('customer', 'like', '%' . $request->customer . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * Создать новый активный заказ и сразу увеличить/уменьшить остатки.
     *
     * @param  array{
     *     customer_name:string,
     *     warehouse_id:int,
     *     items:array<int,array{product_id:int,quantity:int}>
     * } $data
     *
     * @throws \Throwable                     Если транзакция откатится
     * @throws \Exception                     Когда запасов товара недостаточно
     * @return Order                          Созданный объект заказа
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'customer' => $data['customer_name'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => OrderStatus::Active,
            ]);

            foreach ($data['items'] as $item) {
                $this->processItem($order, $item, $data['warehouse_id']);
            }

            return $order;
        });
    }

    /**
     * Списать товар, создать движения и запись в `order_items`.
     *
     * @param  Order                                    $order
     * @param  array{product_id:int,quantity:int}       $item
     * @param  int                                      $warehouseId
     *
     * @throws \Exception  Когда на складе недостаточно товара
     * @return void
     */
    protected function processItem(Order $order, array $item, int $warehouseId): void
    {
        $stock = Stock::where('product_id', $item['product_id'])
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if (!$stock || $stock->stock < $item['quantity']) {
            throw new \Exception("Недостаточно товара ID {$item['product_id']} на складе");
        }

        $stock->stock -= $item['quantity'];
        $stock->save();

        ProductMovement::create([
            'order_id'     => $order->id,
            'product_id'   => $item['product_id'],
            'warehouse_id' => $warehouseId,
            'delta'        => -$item['quantity'],
            'reason'       => 'order',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'count' => $item['quantity'],
        ]);
    }

    /**
     * Откатывает отрицательные движения по заказу,
     * возвращая товар на склад и создавая компенсирующие записи.
     *
     * @param  Order   $order
     * @param  string  $newReason   Причина для компенсирующих движений
     *
     * @return void
     */
    protected function revertMovements(Order $order, string $newReason): void
    {
        $movements = ProductMovement::where('order_id', $order->id)
            ->where('delta', '<', 0)
            ->lockForUpdate()
            ->get();

        foreach ($movements as $mv) {
            $stock = Stock::where('product_id',  $mv->product_id)
                ->where('warehouse_id', $mv->warehouse_id)
                ->lockForUpdate()
                ->firstOrFail();

            $stock->increment('stock', abs($mv->delta));

            ProductMovement::create([
                'order_id'     => $order->id,
                'product_id'   => $mv->product_id,
                'warehouse_id' => $mv->warehouse_id,
                'delta'        => abs($mv->delta),
                'reason'       => $newReason,
            ]);
        }
    }

    /**
     * Обновить заказ: вернуть прежний товар, удалить старые позиции
     * и провести новые.
     *
     * @param  Order  $order
     * @param  array{
     *     customer_name?:string,
     *     warehouse_id:int,
     *     items:array<int,array{product_id:int,quantity:int}>
     * } $data
     *
     * @throws \Exception  Если не хватает товара для новых позиций
     * @return Order       Обновлённый заказ
     */
    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $this->revertMovements($order, 'order_update');
            $order->items()->delete();
            $order->update([
                'customer' => $data['customer_name'] ?? $order->customer,
            ]);
            foreach ($data['items'] as $item) {
                $this->processItem($order, $item, $data['warehouse_id']);
            }

            return $order;
        });
    }

    /**
     * Завершить активный заказ.
     *
     * @param  Order  $order
     *
     * @throws \Exception  Если заказ не активен
     * @return void
     */
    public function complete(Order $order): void
    {
        if ($order->status !== OrderStatus::Active) {
            throw new \Exception('Завершать можно только активный заказ');
        }

        $order->update([
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /**
     * Отменить активный заказ с возвратом товара на склад.
     *
     * @param  Order  $order
     *
     * @throws \Exception  Если заказ не активен
     * @return void
     */
    public function cancel(Order $order): void
    {
        if ($order->status !== OrderStatus::Active) {
            throw new \Exception('Отменять можно только активный заказ');
        }

        DB::transaction(function () use ($order) {
            $this->revertMovements($order, 'order_cancel');

            $order->update(['status' => OrderStatus::Cancelled]);
        });
    }

    /**
     * Возобновить отменённый заказ, снова списав товар.
     *
     * @param  Order  $order
     *
     * @throws \Exception  Если заказ не отменён или товара недостаточно
     * @return void
     */
    public function resume(Order $order): void
    {
        if ($order->status !== OrderStatus::Cancelled) {
            throw new \Exception('Возобновлять можно только отменённый заказ');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $order->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->stock < $item->count) {
                    throw new \Exception("Недостаточно товара ID {$item->product_id} для возобновления");
                }

                $stock->stock -= $item->count;
                $stock->save();

                ProductMovement::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'delta' => -$item->count,
                    'reason' => 'order_resume',
                ]);
            }

            $order->update(['status' => OrderStatus::Active]);
        });
    }
}
