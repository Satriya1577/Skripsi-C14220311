<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function validationLogs()
    {
        return $this->hasMany(ValidationLog::class)->orderBy('period', 'asc');
    }
}
