<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'student';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];


    protected $dates = [
        'created_at',
        'updated_at',
    ];


    public $timestamps = true;


}