<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetabotConversation extends Model
{
    use HasFactory;

    protected $table = 'metabot_conversations';

    protected $fillable = [
        'phone',
        'current_ad_id',
        'current_source_id',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function ad()
    {
        return $this->belongsTo(MetabotAd::class, 'current_ad_id');
    }
}
