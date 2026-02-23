<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;

class ProductTransaction extends Model
{
    protected $guarded = [];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function productionRealization()
    {
        return $this->belongsTo(ProductionRealization::class, 'production_realization_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
