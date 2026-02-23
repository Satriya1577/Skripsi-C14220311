<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function materialTransactions(): HasMany
    {
        return $this->hasMany(MaterialTransaction::class, 'purchase_order_id', 'id');
    }
}
