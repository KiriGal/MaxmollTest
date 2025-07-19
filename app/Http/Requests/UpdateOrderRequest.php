<?php

namespace App\Http\Requests;

use app\Enums\OrderStatus;
use App\Models\Stock;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer' => ['sometimes', 'string'],
            'warehouse_id' => ['sometimes', 'integer', 'exists:warehouses,id'],
            'status' => ['sometimes', new Enum(OrderStatus::class)],
            'completed_at' => ['nullable', 'date'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.product_id.exists' => 'Указанный товар не найден.',
            'items.required_with' => 'Нужно указать хотя бы одну позицию в заказе.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!is_array($this->items)) {
                    return;
                }

                foreach ($this->items as $index => $item) {
                    $totalStock = Stock::where('product_id', $item['product_id'] ?? 0)
                        ->sum('stock');

                    if ($totalStock < ($item['quantity'] ?? 0)) {
                        $validator->errors()->add(
                            "items.$index.quantity",
                            "Недостаточно товара (product_id: {$item['product_id']}) на всех складах. Доступно: $totalStock."
                        );
                    }
                }
            }
        ];
    }
}
