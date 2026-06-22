<?php

namespace App\Jobs;

use App\Mail\BookingNotificationEmail;
use App\Models\Actor;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Operator;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBookingNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $bookingCode,
        public string $event,
        public array $recipients = ['client', 'operator'],
    ) {}

    public function handle(SmsService $smsService): void
    {
        $booking = Booking::query()
            ->with('tour')
            ->where('booking_code', $this->bookingCode)
            ->first();

        if (! $booking) {
            return;
        }

        if (in_array('client', $this->recipients, true)) {
            $client = $booking->client_slug
                ? Client::query()->where('client_slug', $booking->client_slug)->first()
                : null;

            $this->notifyActor($smsService, $client, $booking, 'client');
        }

        if (in_array('operator', $this->recipients, true)) {
            $operator = $booking->operator_slug
                ? Operator::query()->where('operator_slug', $booking->operator_slug)->first()
                : null;

            $this->notifyActor($smsService, $operator, $booking, 'operator');
        }
    }

    protected function notifyActor(SmsService $smsService, ?Actor $actor, Booking $booking, string $audience): void
    {
        if (! $actor) {
            return;
        }

        [$headline, $body, $smsMessage, $actionUrl] = $this->buildContent($booking, $audience);
        $recipientName = trim($actor->first_name . ' ' . ($actor->last_name ?? ''));

        if (! empty($actor->email)) {
            Mail::to($actor->email)->send(new BookingNotificationEmail(
                recipientName: $recipientName ?: $actor->email,
                headline: $headline,
                body: $body,
                booking: $booking,
                actionUrl: $actionUrl,
            ));
        }

        if (! empty($actor->phone_number)) {
            $smsService->send($actor->phone_number, $smsMessage);
        }
    }

    protected function buildContent(Booking $booking, string $audience): array
    {
        $tourName = $booking->tour?->name ?? 'your tour';
        $bookingCode = $booking->booking_code;
        $selectedDate = $booking->selected_date?->format('M j, Y') ?? 'the selected date';
        $amount = number_format((float) $booking->amount, 2) . ' ' . $booking->currency;

        $actionUrl = match ($audience) {
            'operator' => config('custom.urls.operator_url'),
            default => config('custom.urls.client_url'),
        };

        return match ($this->event) {
            'booking_created' => match ($audience) {
                'operator' => $booking->booked_by_type === 'operator'
                    ? [
                        'Booking created',
                        "You created booking {$bookingCode} for {$tourName} on {$selectedDate}.",
                        "AfriQwest: you created booking {$bookingCode} for {$tourName} on {$selectedDate}.",
                        $actionUrl,
                    ]
                    : [
                        'New booking received',
                        "A client placed booking {$bookingCode} for {$tourName} on {$selectedDate}.",
                        "New AfriQwest booking {$bookingCode} for {$tourName} on {$selectedDate}.",
                        $actionUrl,
                    ],
                default => $booking->booked_by_type === 'operator'
                    ? [
                        'Booking created for you',
                        "Booking {$bookingCode} for {$tourName} on {$selectedDate} was created on your behalf.",
                        "AfriQwest: booking {$bookingCode} for {$tourName} on {$selectedDate} was created for you.",
                        $actionUrl,
                    ]
                    : [
                        'Booking submitted',
                        "Your booking {$bookingCode} for {$tourName} on {$selectedDate} was submitted successfully.",
                        "AfriQwest: booking {$bookingCode} for {$tourName} on {$selectedDate} submitted.",
                        $actionUrl,
                    ],
            },
            'booking_updated' => match ($audience) {
                'operator' => [
                    'Booking updated',
                    "Booking {$bookingCode} for {$tourName} was updated.",
                    "AfriQwest: booking {$bookingCode} for {$tourName} was updated.",
                    $actionUrl,
                ],
                default => [
                    'Booking updated',
                    "Your booking {$bookingCode} for {$tourName} was updated successfully.",
                    "AfriQwest: your booking {$bookingCode} for {$tourName} was updated.",
                    $actionUrl,
                ],
            },
            'payment_success' => match ($audience) {
                'operator' => [
                    'Payment successful',
                    "Payment for booking {$bookingCode} ({$tourName}) was successful. Amount paid: {$amount}.",
                    "AfriQwest: payment for booking {$bookingCode} successful. Amount: {$amount}.",
                    $actionUrl,
                ],
                default => [
                    'Payment successful',
                    "Payment for booking {$bookingCode} ({$tourName}) was successful. Amount paid: {$amount}.",
                    "AfriQwest: payment for booking {$bookingCode} successful. Amount: {$amount}.",
                    $actionUrl,
                ],
            },
            'payment_failed' => match ($audience) {
                'operator' => [
                    'Payment failed',
                    "Payment for booking {$bookingCode} ({$tourName}) failed.",
                    "AfriQwest: payment for booking {$bookingCode} failed.",
                    $actionUrl,
                ],
                default => [
                    'Payment failed',
                    "Payment for booking {$bookingCode} ({$tourName}) failed. Please create a new booking or try again.",
                    "AfriQwest: payment for booking {$bookingCode} failed. Please try again.",
                    $actionUrl,
                ],
            },
            default => [
                'Booking notification',
                "There is an update for booking {$bookingCode}.",
                "AfriQwest update for booking {$bookingCode}.",
                $actionUrl,
            ],
        };
    }
}
