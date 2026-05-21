<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'fb_id',
        'descripcion'
    ];

    public function adProducts()
    {
        return $this->hasMany(AdProduct::class, 'id_producto');
    }

    public function adCosts()
    {
        return $this->hasMany(AdCost::class, 'id_producto');
    }
}