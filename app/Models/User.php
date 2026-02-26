<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends \TCG\Voyager\Models\User
{
    protected $table = 'users';

    protected $fillable = [
        'role_id', 'name', 'email', 'mobileno', 'password',
        'highestquali', 'ssc', 'hsc', 'diploma', 'degree',
        'masterdegree', 'aptiscore', 'referenceby', 'post',
        'techroundpercent', 'interviewpercent',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'  => 'datetime',
        'aptiscore'          => 'integer',
        'techroundpercent'   => 'decimal:2',
        'interviewpercent'   => 'decimal:2',
    ];

    protected $attributes = [
    'post' => null,
    ];
    
    // users.role_id → roles.id
    public function role()
    {
        return $this->belongsTo(\TCG\Voyager\Models\Role::class, 'role_id');
    }

    // users.post → post_master.post_id
    public function post()
    {
        return $this->belongsTo(Post::class, 'post', 'post_id');
    }

    // // one user can have many exam sessions
    // public function exams()
    // {
    //     return $this->hasMany(ExamMaster::class, 'CANDIDATEID', 'id');
    // }

    // // shortcut: all exam answer rows for this candidate
    // public function examDetails()
    // {
    //     return $this->hasMany(ExamDetail::class, 'CANDIDATEID', 'id');
    // }

}
