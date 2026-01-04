<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $guarded = [];

    public function transactions()
    {
        return $this->hasMany(MaterialTransaction::class)->orderBy('transaction_date', 'desc');
    }

    public function productMaterials()
    {
        return $this->hasMany(ProductMaterial::class);
    }
}
