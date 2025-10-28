<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $fillable = [
        'id',
        'name',
        'code',
        'version',
        'group_id',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}