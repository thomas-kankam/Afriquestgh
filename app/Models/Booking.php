<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_slug', 'client_slug', 'booked_by_type', 'booked_by_slug', 'tour_slug',
        'booking_type', 'selected_date', 'travelers', 'payment_mode', 'payment_status',
        'amount', 'currency', 'lead_traveler', 'group_details', 'special_requests',
        'dietary_needs', 'additional_travelers', 'status',
    ];

    protected $casts = [
        'selected_date' => 'date',
        'lead_traveler' => 'array',
        'group_details' => 'array',
        'additional_travelers' => 'array',
        'amount' => 'decimal:2',
    ];

    public function getRouteKeyName(): string
    {
        return 'booking_slug';
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_slug', 'tour_slug');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_slug', 'booking_slug');
    }

    public function toBookingArray(?string $paymentUrl = null): array
    {
        $data = [
            'bookingSlug' => $this->booking_slug,
            'clientSlug' => $this->client_slug,
            'bookedByType' => $this->booked_by_type,
            'bookedBySlug' => $this->booked_by_slug,
            'bookingType' => $this->booking_type,
            'tourSlug' => $this->tour_slug,
            'selectedDate' => $this->selected_date?->format('Y-m-d'),
            'travelers' => $this->travelers,
            'paymentMode' => $this->payment_mode,
            'paymentStatus' => $this->payment_status,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'leadTraveler' => $this->lead_traveler,
            'groupDetails' => $this->group_details,
            'specialRequests' => $this->special_requests,
            'dietaryNeeds' => $this->dietary_needs,
            'additionalTravelers' => $this->additional_travelers ?? [],
            'status' => $this->status,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        if ($paymentUrl) {
            $data['paymentUrl'] = $paymentUrl;
        }

        if ($this->relationLoaded('tour') && $this->tour) {
            $data['tour'] = $this->tour->toListingArray();
        }

        return $data;
    }
}
