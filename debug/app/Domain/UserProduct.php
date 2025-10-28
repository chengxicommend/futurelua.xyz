<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class UserProduct extends Model
{
    protected $fillable = [
        'id',
        'member_id',
        'code',
        'version',
        'product_id'
    ];
}