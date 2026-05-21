<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentCod extends Model
{
    use HasFactory;
    protected $table = 'shipments_cod';
    protected $fillable = [
        'fecha_transaccion',
        'guia',
        'numero_operacion',
        'total'
    ];

}