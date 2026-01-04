<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SarimaConfig extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function validationLogs()
    {
        return $this->hasMany(ValidationLog::class)->orderBy('period', 'asc');
    }
}
