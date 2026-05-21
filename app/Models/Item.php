<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        
    ];

    // Define the relationship with the Order model
    public function order()
    {
        return $this->belongsTo(Order::class, 'id_orden');
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'id_variante');
    }

   
}