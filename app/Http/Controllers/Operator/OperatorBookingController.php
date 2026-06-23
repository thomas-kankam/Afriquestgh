<?php

namespace App\Http\Controllers\Operator;

use App\Exceptions\BookingAmountMismatchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\OperatorBookingRules;
use App\Models\Booking;
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
        $bookingType = $request->input('bookingType', $request->input('booking_type', 'group'));

        $request->validate(OperatorBookingRules::store($bookingType));

        $operator = request()->user();
        $tour = Tour::query()
            ->where('tour_slug', $request->input('tourSlug'))
            ->where('operator_slug', $operator->operator_slug)
            ->firstOrFail();

        $clientSlug = $request->input('clientSlug') ?? $request->input('client_slug');

        try {
            $result = $this->bookingService->create(
                array_merge($request->all(), ['tourSlug' => $tour->tour_slug]),
                'operator',
                $operator->operator_slug,
                $clientSlug
            );
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Booking created', $result);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOperatorBooking($booking);

        if ($booking->booked_by_type !== 'operator') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Only manually created bookings can be edited', []);
        }

        $bookingType = $request->input('bookingType', $request->input('booking_type', $booking->booking_type));

        $request->validate(OperatorBookingRules::update($bookingType));

        try {
            $result = $this->bookingService->updateOperatorBooking($booking, $request->all());
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        } catch (\RuntimeException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking updated', $result);
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
