<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetabotTemplate extends Model
{
    use HasFactory;

    protected $table = 'metabot_templates';

    protected $fillable = [
        'name',
        'language',
        'label',
        'body_preview',
        'status',
    ];
}
