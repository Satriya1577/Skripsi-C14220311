<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date', 
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function productionRealization()
    {
        return $this->belongsTo(ProductionRealization::class, 'production_realization_id');
    }
}
