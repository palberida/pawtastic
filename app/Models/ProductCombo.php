<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCombo extends Model
{
    use HasFactory;
    protected $table = 'product_combos';

    protected $fillable = [
        'descripcion',
        'id_shopify'
    ];

    
}