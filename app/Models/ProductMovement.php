<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMovement extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'product_id', 'warehouse_id', 'delta', 'reason',];

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse() : BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
