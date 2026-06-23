<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->with(['booking.tour', 'booking.client', 'booking.operator'])
            ->latest();

        if ($request->filled('status')) {
            $status = Payment::statusFilterValue((string) $request->status);

            if ($status) {
                $query->where('status', $status);
            }
        }

        $paymentMode = $this->resolvePaymentModeFilter($request);

        if ($paymentMode) {
            $query->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('payment_mode', $paymentMode));
        }

        $this->applyOperatorFilter($query, $request);
        $this->applyClientFilter($query, $request);

        if ($request->filled('booking_code') || $request->filled('bookingCode')) {
            $bookingCode = $request->input('bookingCode', $request->input('booking_code'));
            $query->where('booking_code', $bookingCode);
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Payments retrieved', $paginator, fn (Payment $payment) => $payment->toPaymentArray());
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['booking.tour', 'booking.client', 'booking.operator']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Payment retrieved', $payment->toPaymentArray());
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

    protected function applyOperatorFilter(Builder $query, Request $request): void
    {
        if ($request->filled('operator_slug') || $request->filled('operatorSlug')) {
            $operatorSlug = $request->input('operatorSlug', $request->input('operator_slug'));
            $query->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('operator_slug', $operatorSlug));

            return;
        }

        $operatorSearch = $this->searchTerm($request, ['operator', 'operatorName', 'operator_name']);

        if (! $operatorSearch) {
            return;
        }

        $query->whereHas('booking.operator', function (Builder $operatorQuery) use ($operatorSearch) {
            $operatorQuery->where(function (Builder $nameQuery) use ($operatorSearch) {
                $term = '%' . $operatorSearch . '%';
                $nameQuery->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('organization', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$term]);
            });
        });
    }

    protected function applyClientFilter(Builder $query, Request $request): void
    {
        if ($request->filled('client_slug') || $request->filled('clientSlug')) {
            $clientSlug = $request->input('clientSlug', $request->input('client_slug'));
            $query->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery->where('client_slug', $clientSlug));

            return;
        }

        $clientSearch = $this->searchTerm($request, ['client', 'clientName', 'client_name']);

        if (! $clientSearch) {
            return;
        }

        $query->whereHas('booking.client', function (Builder $clientQuery) use ($clientSearch) {
            $clientQuery->where(function (Builder $nameQuery) use ($clientSearch) {
                $term = '%' . $clientSearch . '%';
                $nameQuery->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone_number', 'like', $term)
                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$term]);
            });
        });
    }

    protected function searchTerm(Request $request, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) $request->input($key, ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
