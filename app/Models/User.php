<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Database\Eloquent\SoftDeletes; 

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    protected $fillable = [
        'prenom',
        'nom',
        'tel',
        'email',
        'password',
        'is_blocked',        // si tu ajoutes ce champ aussi
        'blocked_until',     // tu peux aussi le laisser ici
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_blocked' => 'boolean',  // si tu utilises ce champ
    ];

    public function twoFactorVerify($code)
    {
        $google2fa = new Google2FA();

        // Décrypter la clé secrète 2FA
        $secret = decrypt($this->two_factor_secret);

        // Vérifier si le code fourni par l'utilisateur est valide
        return $google2fa->verifyKey($secret, $code);
    }
}
