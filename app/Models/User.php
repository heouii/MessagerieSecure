<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'prenom',
        'nom',
        'tel',
        'email',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'deleted_at',
        'security_question',
        'security_answer',
        'created_at',
        'updated_at',
        'is_blocked',
        'admin',
        'blocked_until',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_blocked' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function hasTwoFactorEnabled()
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorVerify($code)
    {
        $secret = $this->two_factor_secret;
        $google2fa = new Google2FA();
        return $google2fa->verifyKey($secret, $code);
    }

    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('security_question')->nullable();
        $table->string('security_answer')->nullable();
    });
}

}
