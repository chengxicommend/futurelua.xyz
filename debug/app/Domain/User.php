<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'id',
        'name',
        'pass_hash',
        'email',
        'hwid',
        'hwid_mismatch',
        'referer',
        'permission_level',
        'additional_groups',
        'banned',
        'ban_reason',
        'isChinese'
    ];
}