<?php
/**
 * Created by SamHoque
 * Self Explanatory
 */

namespace App\Domain;

use Illuminate\Database\Eloquent\Model;

class Helper extends Model
{
    protected $fillable = [
        'id',
        'name',
        'code',
        'version',
        'group_id',
    ];
}