<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function sales()
    {
        return $this->hasMany(Sales::class, 'product_id', 'id');
    }

    public function productMaterials()
    {
        return $this->hasMany(ProductMaterial::class);
    }

    public function sarimaConfig()
    {
        return $this->hasOne(SarimaConfig::class, 'product_id', 'id');
    }
}
