<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryHistory extends Model
{
    use HasFactory;

    protected $dates = ['fecha'];
    protected $table = 'inventory_history';
    protected $fillable = [
       
    ];

}