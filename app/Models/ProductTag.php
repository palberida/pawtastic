<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTag extends Model
{
    use HasFactory;

    protected $table = 'product_tags';

    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_variante',
        'tag',
        'valor',
    ];
}
