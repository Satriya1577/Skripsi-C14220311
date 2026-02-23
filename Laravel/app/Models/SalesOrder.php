<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $guarded = [];

    // Relasi ke Item
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesPayment::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function productTransactions(): HasMany
    {
        return $this->hasMany(ProductTransaction::class, 'sales_order_id', 'id');
    }
}
