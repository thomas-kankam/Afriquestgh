<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\BookingService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaystackService $paystack,
        protected BookingService $bookingService
    ) {}

    public function callback(Request $request): JsonResponse
    {
        $reference = $request->query('reference') ?? $request->input('trxref');
        Log::info('Payment callback', ['reference' => $reference]);

        if (! $reference) {
            Log::info('Payment callback failed', ['request' => $request->all()]);
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Missing payment reference', []);
        }

        try {
            $data = $this->paystack->verifyTransaction($reference);

            if (($data['status'] ?? '') === 'success') {
                $this->bookingService->markPaidByReference($reference, $data);
            } else {
                $this->bookingService->markFailedByReference($reference, $data);
            }

            $payment = Payment::query()->where('paystack_reference', $reference)->with('booking.tour')->first();
            // Log::info('Payment verified', ['payment' => $payment]);
            return self::apiResponse(
                false,
                'Action Successful',
                (string) self::API_SUCCESS,
                'Payment verified',
                [
                    'reference' => $reference,
                    'status' => $data['status'] ?? 'unknown',
                    'booking' => $payment?->booking?->toBookingArray(),
                ]
            );
        } catch (\Throwable $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, $e->getMessage(), []);
        }
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
