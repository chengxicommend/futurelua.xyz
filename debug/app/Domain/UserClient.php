<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class UserClient extends Model
{
    protected $fillable = [
        'id',
        'member_id',
        'code',
        'version',
        'group_id'
    ];
}