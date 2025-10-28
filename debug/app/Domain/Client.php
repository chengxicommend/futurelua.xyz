<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'id',
        'code',
        'version',
        'group_id'
    ];
}