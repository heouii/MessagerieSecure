<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use PragmaRX\Google2FA\Google2FA;


class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'prenom',  // Ajouté
        'nom',     // Ajouté
        'tel',     // Ajouté
        'email',
        'password',
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