<?php

namespace App\Models;

class Role extends \TCG\Voyager\Models\Role
{
    protected $table = 'roles'; // change to your actual table name

    protected $fillable = [
        'name',
        'display_name',
    ];
}