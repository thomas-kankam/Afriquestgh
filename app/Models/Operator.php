<?php

namespace App\Models;

class Operator extends Actor
{
    protected $fillable = [
        'operator_slug',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'organization',
        'location',
        'is_verified',
        'verified_at',
        'status',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'status' => 'string',
    ];

    public function getRouteKeyName(): string
    {
        return 'operator_slug';
    }
}
