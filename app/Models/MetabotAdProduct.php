<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetabotAdProduct extends Model
{
    use HasFactory;

    protected $table = 'metabot_ad_products';

    protected $fillable = [
        'metabot_ad_id',
        'id_producto',
    ];

    public function ad()
    {
        return $this->belongsTo(MetabotAd::class, 'metabot_ad_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_producto');
    }
}
