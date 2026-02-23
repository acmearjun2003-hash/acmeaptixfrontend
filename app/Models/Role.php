<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles'; // change to your actual table name

    protected $fillable = [
        'name',
        'display_name',
    ];
}