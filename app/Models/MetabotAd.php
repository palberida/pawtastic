<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetabotAd extends Model
{
    use HasFactory;

    protected $table = 'metabot_ads';

    protected $fillable = [
        'source_id',
        'name',
        'scope',
        'welcome_text',
        'status',
    ];

    /**
     * Products mapped to this ad (product_set scope only).
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'metabot_ad_products',
            'metabot_ad_id',
            'id_producto'
        )->withTimestamps();
    }

    public function adProducts()
    {
        return $this->hasMany(MetabotAdProduct::class, 'metabot_ad_id');
    }
}
