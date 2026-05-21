<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_cliente',
        'direccion_cliente',
        'departamento_cliente',
        'municipio_cliente',
        'telefono1_cliente',
        'telefono2_cliente',
        'mensajero',
        'total'
    ];
}