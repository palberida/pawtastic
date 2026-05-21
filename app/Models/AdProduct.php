<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_ad',
        'id_producto'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_producto');
    }
    
    public function ad()
    {
        return $this->belongsTo(Ad::class, 'id_ad');
    }


}