<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTracking extends Model
{
    use HasFactory;

    protected $table = 'email_tracking';

    protected $fillable = [
        'message_id',
        'event_type',
        'event_data',
        'occurred_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(SecureMessage::class, 'message_id');
    }
}