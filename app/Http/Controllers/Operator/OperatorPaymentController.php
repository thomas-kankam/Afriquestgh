<?php

namespace App\Http\Controllers\Operator;

use App\Exceptions\BookingAmountMismatchException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorPaymentController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $operator = request()->user();
        $query = Payment::query()
            ->with(['booking.tour'])
            ->whereHas('booking', fn(Builder $bookingQuery) => $bookingQuery->where('operator_slug', $operator->operator_slug))
            ->latest();

        if ($request->filled('status')) {
            $status = Payment::statusFilterValue((string) $request->status);

            if ($status) {
                $query->where('status', $status);
            }
        }

        $paymentMode = $this->resolvePaymentModeFilter($request);

        if ($paymentMode) {
            $query->whereHas('booking', fn(Builder $bookingQuery) => $bookingQuery->where('payment_mode', $paymentMode));
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Payments retrieved', $paginator, fn(Payment $payment) => $payment->toPaymentArray());
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorizeOperatorPayment($payment);
        $payment->load(['booking.tour']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Payment retrieved', $payment->toPaymentArray());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'bookingCode' => 'required_without:booking_code|string|exists:bookings,booking_code',
            'booking_code' => 'required_without:bookingCode|string|exists:bookings,booking_code',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $operator = request()->user();
        $bookingCode = $request->input('bookingCode', $request->input('booking_code'));

        $booking = Booking::query()
            ->where('booking_code', $bookingCode)
            ->where('operator_slug', $operator->operator_slug)
            ->firstOrFail();

        try {
            $result = $this->bookingService->recordOnsitePayment(
                $booking,
                $operator->operator_slug,
                $request->input('amount') !== null ? (float) $request->input('amount') : null
            );
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        } catch (\RuntimeException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Offline payment recorded', $result);
    }

    protected function authorizeOperatorPayment(Payment $payment): void
    {
        $payment->loadMissing('booking');

        if ($payment->booking?->operator_slug !== request()->user()->operator_slug) {
            abort(403, 'Payment not found');
        }
    }

    protected function resolvePaymentModeFilter(Request $request): ?string
    {
        $paymentMode = $request->input('paymentMode', $request->input('payment_mode'));
        $bookingType = $request->input('bookingType', $request->input('booking_type'));

        if (in_array($bookingType, ['online', 'onsite'], true)) {
            return $bookingType;
        }

        return in_array($paymentMode, ['online', 'onsite'], true) ? $paymentMode : null;
    }
}
