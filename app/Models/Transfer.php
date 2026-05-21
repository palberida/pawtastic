<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'descripcion',
        'total',
        'rango_inicio',
        'rango_fin'
    ];

    protected $dates = [
        'rango_inicio',
        'rango_fin',
        'created_at',
        'updated_at',
    ];
    
    public function transferOrders()
    {
        return $this->hasMany(TransferOrder::class, 'id_transferencia');
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, TransferOrder::class, 'id_transferencia', 'id', 'id', 'id_orden');
    }
}