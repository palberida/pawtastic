<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_ad',
        'costo',
        'dia',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'id_ad');
    }
    


}