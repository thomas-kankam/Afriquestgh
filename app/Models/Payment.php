<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_slug', 'booking_code', 'paystack_reference', 'paystack_access_code',
        'amount', 'currency', 'status', 'payment_url', 'paystack_response', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paystack_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'payment_slug';
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_code', 'booking_code');
    }
}
