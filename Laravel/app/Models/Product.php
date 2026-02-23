<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class, 'product_id', 'id');
    }

    public function productMaterials()
    {
        return $this->hasMany(ProductMaterial::class);
    }


    public function transactions()
    {
        return $this->hasMany(ProductTransaction::class);
    }
}
