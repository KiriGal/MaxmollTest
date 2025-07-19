<?php

namespace App\Services;

use App\Http\Requests\ProductMovementFilterRequest;
use App\Models\ProductMovement;

class ProductMovementService
{
    /**
     * Вернуть список движений товара, отфильтрованный и постраничный.
     *
     * **Поддерживаемые query-параметры (валидируются в `ProductMovementFilterRequest`):**
     * - `product_id`   int     ― конкретный товар
     * - `warehouse_id` int     ― конкретный склад
     * - `from_date`    Y-m-d   ― с какой даты включительно (`created_at ≥ from_date`)
     * - `to_date`      Y-m-d   ― по какую дату включительно (`created_at ≤ to_date`)
     * - `per_page`     int     ― размер страницы (по умолчанию 15)
     *
     * Метод всегда подтягивает связи `product` и `warehouse`, сортирует
     * движения по убыванию даты создания.
     *
     * @param  ProductMovementFilterRequest          $request
     * @return LengthAwarePaginator<ProductMovement> Постраничная коллекция движений
     */
    public function getFilteredMovements(ProductMovementFilterRequest $request)
    {
        $query = ProductMovement::query();

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->from_date) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $perPage = $request->input('per_page', 15);

        return $query
            ->with(['product', 'warehouse'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

}
