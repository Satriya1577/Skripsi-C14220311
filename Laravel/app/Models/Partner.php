<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = [];

    public function pricelists()
    {
        return $this->hasMany(PartnerPricelist::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'partner_pricelists')
                    ->withPivot('id', 'price', 'minimum_order_qty', 'supplier_lead_time_days', 'is_active')
                    ->withTimestamps();
    }
}
