<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProblem extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_orden',
        'notas',
        'dia',
        'tipo'
    ];
}