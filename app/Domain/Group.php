<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'id',
        'name',
        'admins',
        'resellers'
    ];
}