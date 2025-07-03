<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SecureMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'recipient_email',
        'subject',
        'encrypted_content',
        'message_token',
        'encryption_key',
        'mailgun_id',
        'expires_at',
        'read_at',
        'self_destructed_at',
        'require_2fa',
        'self_destruct',
        'read_receipt',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'read_at' => 'datetime',
        'self_destructed_at' => 'datetime',
        'require_2fa' => 'boolean',
        'self_destruct' => 'boolean',
        'read_receipt' => 'boolean',
        'metadata' => 'array',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function tracking()
    {
        return $this->hasMany(EmailTracking::class, 'message_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return !$this->isExpired() && !$this->self_destructed_at;
    }

    public function decrypt(): string
    {
        if (!$this->isAccessible()) {
            throw new \Exception('Message non accessible');
        }
        return decrypt($this->encrypted_content);
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => Carbon::now()]);
            
            if ($this->self_destruct) {
                $this->update(['self_destructed_at' => Carbon::now()]);
            }
        }
    }
}