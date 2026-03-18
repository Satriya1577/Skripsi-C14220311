<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    protected $guarded = [];

    public function productionRealizations() { 
        return $this->hasMany(ProductionRealization::class); 
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

}
