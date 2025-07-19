<?php

namespace App\Models;

use app\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['customer', 'created_at', 'completed_at', 'warehouse_id', 'status'];

    protected $casts = [
        'status' => OrderStatus::class,
    ];

    public function warehouse() : BelongsTo {
        return $this->belongsTo(Warehouse::class);
    }
    public function orderItems() : HasMany {
        return $this->hasMany(OrderItem::class);
    }
}
