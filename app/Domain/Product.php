<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
	protected $fillable = [
		'id',
		'name',
		'code',
		'version',
        'category',
        'group_id',
        'priority',
        'disable_obfuscation'
	];

    public function configs()
    {
        return $this->belongsToMany(Config::class);
    }
}