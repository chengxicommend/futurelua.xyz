<?php

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
	protected $fillable = [
		'id',
		'group_id',
		'admin_id'
	];

    public function configs()
    {
        return $this->belongsToMany(Config::class);
    }
}