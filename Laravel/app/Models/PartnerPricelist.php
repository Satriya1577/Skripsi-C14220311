<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerPricelist extends Model
{
    protected $table = 'partner_pricelists';
    protected $guarded = [];

    // Relasi balik ke Partner
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    // Relasi balik ke Material
    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}