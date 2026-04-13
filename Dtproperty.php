<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dtproperty extends Model
{
    protected $table = 'dtproperties';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'objectid',
        'property',
        'value',
        'uvalue',
        'lvalue',
        'version'
    ];
}