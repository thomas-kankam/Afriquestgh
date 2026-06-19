<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Tour;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorBookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $operator = request()->user();
        $query = Booking::query()
            ->with('tour')
            ->where('operator_slug', $operator->operator_slug)
            ->latest();

        if ($request->filled('booked_by')) {
            if ($request->booked_by === 'operator') {
                $query->where('booked_by_type', 'operator');
            }

            if ($request->booked_by === 'client') {
                $query->where('booked_by_type', 'client');
            }
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Bookings retrieved', $paginator, fn (Booking $booking) => $booking->toBookingArray());
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorizeOperatorBooking($booking);
        $booking->load('tour');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking retrieved', $booking->toBookingArray());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tourSlug' => 'required|string|exists:tours,tour_slug',
            'selectedDate' => 'required|date',
            'travelers' => 'required|integer|min:1',
            'paymentMode' => 'required|in:online,onsite',
            'leadTraveler' => 'required|array',
            'leadTraveler.email' => 'required|email',
            'clientSlug' => 'nullable|string|exists:clients,client_slug',
        ]);

        $operator = request()->user();
        $tour = Tour::query()
            ->where('tour_slug', $request->input('tourSlug'))
            ->where('operator_slug', $operator->operator_slug)
            ->firstOrFail();

        $clientSlug = $request->input('clientSlug') ?? $request->input('client_slug');

        if ($clientSlug && ! Client::query()->where('client_slug', $clientSlug)->exists()) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Client not found', []);
        }

        $result = $this->bookingService->create(
            array_merge($request->all(), ['tourSlug' => $tour->tour_slug]),
            'operator',
            $operator->operator_slug,
            $clientSlug
        );

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Booking created', $result);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOperatorBooking($booking);

        if ($booking->booked_by_type !== 'operator') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Only manually created bookings can be edited', []);
        }

        $data = $request->validate([
            'status' => 'sometimes|string',
            'payment_status' => 'sometimes|string',
            'special_requests' => 'nullable|string',
            'dietary_needs' => 'nullable|string',
            'additional_travelers' => 'nullable|array',
            'selected_date' => 'sometimes|date',
            'selectedDate' => 'sometimes|date',
            'travelers' => 'sometimes|integer|min:1',
        ]);

        $booking->update(array_filter([
            'status' => $data['status'] ?? null,
            'payment_status' => $data['payment_status'] ?? null,
            'special_requests' => $data['special_requests'] ?? $request->input('specialRequests'),
            'dietary_needs' => $data['dietary_needs'] ?? $request->input('dietaryNeeds'),
            'additional_travelers' => $data['additional_travelers'] ?? $request->input('additionalTravelers'),
            'selected_date' => $data['selected_date'] ?? $data['selectedDate'] ?? null,
            'travelers' => $data['travelers'] ?? null,
        ], fn ($value) => $value !== null));

        $booking->load('tour');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking updated', $booking->toBookingArray());
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $this->authorizeOperatorBooking($booking);

        if ($booking->booked_by_type !== 'operator') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Only manually created bookings can be deleted', []);
        }

        $booking->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking deleted', []);
    }

    protected function authorizeOperatorBooking(Booking $booking): void
    {
        if ($booking->operator_slug !== request()->user()->operator_slug) {
            abort(403, 'Booking not found');
        }
    }
}
