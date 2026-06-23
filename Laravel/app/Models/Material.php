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

    public function pricelists()
    {
        return $this->hasMany(PartnerPricelist::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Partner::class, 'partner_pricelists')
                    ->wherePivot('is_active', true) // Hanya ambil supplier yang harganya masih aktif
                    ->withPivot('id', 'price', 'minimum_order_qty', 'supplier_lead_time_days')
                    ->withTimestamps();
    }
}
