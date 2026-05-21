<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_orden',
        'id_transferencia',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'id_orden');
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class, 'id_transferencia');
    }
}