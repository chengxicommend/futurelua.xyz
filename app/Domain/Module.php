<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'id',
        'name',
        'code',
        'version',
        'priority'
    ];
}