<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovedSender extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'domain',
    ];
}