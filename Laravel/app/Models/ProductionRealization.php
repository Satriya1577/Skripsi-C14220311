<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionRealization extends Model
{
    protected $guarded = [];

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class);
    }
}
