<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'profile_image',
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
        'profile_image' => 'string',
    ];

    public function getRouteKeyName(): string
    {
        return 'operator_slug';
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class, 'operator_slug', 'operator_slug');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'operator_slug', 'operator_slug');
    }

    public function toOperatorArray(): array
    {
        return [
            'operatorSlug' => $this->operator_slug,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'phoneNumber' => $this->phone_number,
            'organization' => $this->organization,
            'location' => $this->location,
            'isVerified' => $this->is_verified,
            'verifiedAt' => $this->verified_at,
            'status' => $this->status,
            'profileImage' => $this->profile_image,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
