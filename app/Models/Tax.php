<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $primaryKey = 'numero_formulario';
    
    protected $fillable = [
        'numero_formulario',
        'numero_acceso',
        'banco',
        'fecha_presentacion',
        'total'
    ];

}