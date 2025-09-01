<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalConfig extends Model
{
    use SoftDeletes;
    
    protected $table = 'global_config';
    
    protected $fillable = [
        'name',
        'description',
        'key',
        'value',
        'group',
        'type',
        'itype',
        'options',
        'status'
    ];
    
    protected $casts = [
        'type' => 'integer',
        'itype' => 'integer',
        'status' => 'integer',
        'options' => 'array'
    ];
}