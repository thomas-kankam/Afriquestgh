<?php

namespace App\Services;

use App\Exceptions\BookingAmountMismatchException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Tour;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(protected PaystackService $paystack) {}

    public function create(array $payload, string $bookedByType, string $bookedBySlug, ?string $clientSlug = null): array
    {
        $tour = Tour::query()->where('tour_slug', $payload['tourSlug'] ?? $payload['tour_slug'])->firstOrFail();
        $travelers = (int) ($payload['travelers'] ?? 1);
        $paymentMode = $payload['paymentMode'] ?? $payload['payment_mode'] ?? 'onsite';
        $amount = $this->calculateAmount($tour, $travelers);
        $providedAmount = $payload['amount'] ?? null;

        if ($providedAmount !== null && round((float) $providedAmount, 2) !== $amount) {
            throw new BookingAmountMismatchException();
        }

        $booking = Booking::create([
            'booking_slug' => (string) Str::uuid(),
            'client_slug' => $clientSlug,
            'booked_by_type' => $bookedByType,
            'booked_by_slug' => $bookedBySlug,
            'tour_slug' => $tour->tour_slug,
            'booking_type' => $payload['bookingType'] ?? $payload['booking_type'] ?? 'group',
            'selected_date' => $payload['selectedDate'] ?? $payload['selected_date'],
            'travelers' => $travelers,
            'payment_mode' => $paymentMode,
            'payment_status' => $paymentMode === 'online' ? 'pending' : 'onsite',
            'amount' => $amount,
            'currency' => $tour->price_currency,
            'lead_traveler' => $payload['leadTraveler'] ?? $payload['lead_traveler'] ?? [],
            'group_details' => $payload['groupDetails'] ?? $payload['group_details'] ?? null,
            'special_requests' => $payload['specialRequests'] ?? $payload['special_requests'] ?? null,
            'dietary_needs' => $payload['dietaryNeeds'] ?? $payload['dietary_needs'] ?? null,
            'additional_travelers' => $payload['additionalTravelers'] ?? $payload['additional_travelers'] ?? [],
            'status' => 'pending',
            'operator_slug' => $bookedByType === 'operator' ? $bookedBySlug : $tour->operator_slug,
            'created_by_admin_slug' => $bookedByType === 'admin' ? $bookedBySlug : null,
        ]);

        $paymentUrl = null;

        if ($paymentMode === 'online') {
            $email = $booking->lead_traveler['email'] ?? 'customer@afriquestgh.com';
            $initialized = $this->paystack->initializeTransaction(
                email: $email,
                amount: $amount,
                currency: $tour->price_currency,
                metadata: [
                    'booking_slug' => $booking->booking_slug,
                    'tour_slug' => $tour->tour_slug,
                ]
            );

            Payment::create([
                'payment_slug' => (string) Str::uuid(),
                'booking_slug' => $booking->booking_slug,
                'paystack_reference' => $initialized['reference'],
                'paystack_access_code' => $initialized['access_code'],
                'amount' => $amount,
                'currency' => $tour->price_currency,
                'status' => 'pending',
                'payment_url' => $initialized['authorization_url'],
                'paystack_response' => $initialized['raw'],
            ]);

            $paymentUrl = $initialized['authorization_url'];
        }

        $booking->load('tour');

        return $booking->toBookingArray($paymentUrl);
    }

    public function markPaidByReference(string $reference, array $paystackData): void
    {
        $payment = Payment::query()->where('paystack_reference', $reference)->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status' => 'success',
            'paystack_response' => $paystackData,
            'paid_at' => now(),
        ]);

        $payment->booking?->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
    }

    protected function calculateAmount(Tour $tour, int $travelers): float
    {
        $base = (float) $tour->price_amount * $travelers;
        // $settings = $tour->booking_settings ?? [];
        // $depositPercent = (int) ($settings['depositPercent'] ?? 100);

        return round($base, 2);
    }
}
