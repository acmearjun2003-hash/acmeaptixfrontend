<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    // Maps to the post_master table
    protected $table = 'post_master';

    // Primary key column
    protected $primaryKey = 'post_id';

    // Voyager / Carbon handle timestamps automatically
    public $timestamps = true;

    // Voyager stores created_at / updated_at as DATETIME
    // so we keep the default format
    protected $fillable = [
        'post_name',
        'department',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationship ─────────────────────────────────────────
    // One Post → Many Users
    // Users.post is now an INT (FK → post_master.post_id)
    public function users()
    {
        return $this->hasMany(
            \App\Models\User::class,
            'post',        // FK column in users table
            'post_id'      // PK column in post_master
        );
    }

    // ── Scope: only active posts ─────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Display helper used by Voyager relationship dropdowns ─
    public function getPostNameWithDeptAttribute(): string
    {
        return $this->department
            ? "{$this->post_name} ({$this->department})"
            : $this->post_name;
    }

}

