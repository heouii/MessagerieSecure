<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'receiver_id', 'sujet', 'contenu', 'status', 'piece_jointe'
    ];

    // Relation avec l'expéditeur (utilisateur qui envoie le message)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // 'user_id' est la clé étrangère
    }

    // Relation avec le destinataire (utilisateur qui reçoit le message)
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id'); // 'receiver_id' est la clé étrangère
    }
}
