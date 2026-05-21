<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = [
        'descripcion',
        'codigo',
        'precio',
        'costo',
        'id_shopify',
        'id_shopify_inventory'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_producto');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

}