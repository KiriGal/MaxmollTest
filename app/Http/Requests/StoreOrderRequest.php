<?php

namespace App\Http\Requests;

use app\Enums\OrderStatus;
use App\Models\Stock;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrderRequest extends FormRequest
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
            'customer' => ['required', 'string'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'status' => ['required', new Enum(OrderStatus::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Нужно указать хотя бы одну позицию в заказе.',
            'items.*.product_id.exists' => 'Указанный товар не найден.',
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
                            "Недостаточно товара (product_id: {$item['product_id']}) на всех складах. Остаток: $totalStock."
                        );
                    }
                }
            }
        ];
    }
}
