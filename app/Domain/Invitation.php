<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'id',
        'code',
        'inviter',
        'invitee',
        'permission_level'
    ];
}