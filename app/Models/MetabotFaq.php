<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetabotFaq extends Model
{
    use HasFactory;

    protected $table = 'metabot_faq';

    protected $fillable = [
        'topic',
        'trigger_description',
        'answer_text',
        'status',
    ];
}
