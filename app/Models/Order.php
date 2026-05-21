<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_shopify',
        'nombre_cliente',
        'direccion_cliente',
        'departamento_cliente',
        'municipio_cliente',
        'telefono1_cliente',
        'telefono2_cliente',
        'email_cliente',
        'nit_cliente',
        'mensajero',
        'total',
        'forma_pago',
        'guia',
        'costo_envio_aproximado',
        'notas',
        'estado',
        'pagado',
        'notas_envio',
        'vendedor',
        'bank_statement_id'
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'id_orden');
    }

    public function transfers()
    {
        return $this->hasMany(TransferOrder::class, 'id_orden')->with('transfer');
    }
}