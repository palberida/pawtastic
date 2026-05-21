<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'valor',
        'moneda',
        'dia',
        'tipo',
        'tipo_pago',
        'proveedor',
        'descripcion',
        'fin',
        'inicio'
    ];
}