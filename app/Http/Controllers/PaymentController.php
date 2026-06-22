<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\BookingService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaystackService $paystack,
        protected BookingService $bookingService
    ) {}

    public function callback(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim(config('custom.urls.frontend_url', 'https://afriquestgh.netlify.app'), '/');
        $reference = $request->query('reference') ?? $request->input('trxref');
        Log::info('Payment callback', ['reference' => $reference]);

        if (! $reference) {
            Log::info('Payment callback failed', ['request' => $request->all()]);

            return $this->redirectWithApiResponse($frontendUrl, true, 'Action Unsuccessful', (string) self::API_FAIL, 'Missing payment reference', []);
        }

        try {
            $data = $this->paystack->verifyTransaction($reference);
            $paymentStatus = ($data['status'] ?? '') === 'success' ? 'success' : 'failed';

            if ($paymentStatus === 'success') {
                $this->bookingService->markPaidByReference($reference, $data);
            } else {
                $this->bookingService->markFailedByReference($reference, $data);
            }

            $payment = Payment::query()->where('paystack_reference', $reference)->with('booking.tour')->first();
            $booking = $payment?->booking;

            $isSuccess = $paymentStatus === 'success';

            return $this->redirectWithApiResponse(
                $frontendUrl,
                $isSuccess,
                $isSuccess ? 'Action Successful' : 'Action Unsuccessful',
                (string) ($isSuccess ? self::API_SUCCESS : self::API_FAIL),
                $isSuccess ? 'Payment verified' : 'Payment failed',
                [
                    'reference' => $reference,
                    'status' => $paymentStatus,
                    'booking' => $booking?->toBookingArray(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Payment callback error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return $this->redirectWithApiResponse($frontendUrl, true, 'Action Unsuccessful', (string) self::API_FAIL, $e->getMessage(), [
                'reference' => $reference,
                'status' => 'failed',
            ]);
        }
    }

    protected function redirectWithApiResponse(
        string $frontendUrl,
        bool $inError,
        string $message,
        int|string $statusCode,
        string $reason,
        ?array $data = [],
    ): RedirectResponse {
        $payload = [
            'data' => [
                'status_code' => (string) $statusCode,
                'message' => $message,
                'in_error' => $inError,
                'reason' => $reason,
                'data' => $data ?? [],
                'point_in_time' => now(),
            ],
        ];

        return redirect()->away(
            $frontendUrl . '?response=' . urlencode(base64_encode(json_encode($payload)))
        );
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (! $this->paystack->validateWebhookSignature($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);

        if ($event === 'charge.success' && ! empty($data['reference'])) {
            $this->bookingService->markPaidByReference($data['reference'], $data);
        }

        if (in_array($event, ['charge.failed', 'payment.failed'], true) && ! empty($data['reference'])) {
            $this->bookingService->markFailedByReference($data['reference'], $data);
        }

        return response()->json(['message' => 'Webhook received']);
    }
}
