<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Email extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'mailgun_id',
        'folder',
        'from_email',
        'from_name',
        'to_email',
        'to_name',
        'cc_email',
        'subject',
        'content',
        'preview',
        'is_html',
        'is_read',
        'is_starred',
        'read_at',
        'attachments',
        'metadata',
	'signature_verified',
    'is_spam',
    'spam_probability',
    'spam_confidence',
    'spam_checked_at',
    'spam_details',
    ];

    protected $casts = [
        'is_html' => 'boolean',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
		'signature_verified' => 'boolean',  // AJOUTER
    'is_spam' => 'boolean', 
        'read_at' => 'datetime',
	'spam_checked_at' => 'datetime',
        'attachments' => 'array',
        'metadata' => 'array',
	'spam_details' => 'array', 
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeInbox($query)
    {
        return $query->where('folder', 'inbox');
    }

    public function scopeSent($query)
    {
        return $query->where('folder', 'sent');
    }

    public function scopeDrafts($query)
    {
        return $query->where('folder', 'drafts');
    }

    public function scopeTrash($query)
    {
        return $query->where('folder', 'trash');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // Méthodes
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => Carbon::now()
        ]);
    }

    public function moveToTrash()
    {
        $this->update(['folder' => 'trash']);
    }

    public function generatePreview($length = 100)
    {
        $text = $this->is_html ? strip_tags($this->content) : $this->content;
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }
}
