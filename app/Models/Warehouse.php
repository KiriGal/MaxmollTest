<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name'];

    public function stock() : HasMany {
        return $this->hasMany(Stock::class);
    }
    public function orders() : HasMany {
        return $this->hasMany(Order::class);
    }
}
