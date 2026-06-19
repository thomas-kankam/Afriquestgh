<?php

namespace App\Http\Controllers\Client;

use App\Exceptions\BookingAmountMismatchException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientBookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $client = request()->user();
        $paginator = self::paginateQuery(
            $request,
            Booking::query()
                ->with('tour')
                ->where('client_slug', $client->client_slug)
                ->latest()
        );

        return self::paginatedApiResponse('Bookings retrieved', $paginator, fn (Booking $booking) => $booking->toBookingArray());
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorizeClientBooking($booking);

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
            'amount' => 'required|numeric|min:0',
        ]);

        $client = request()->user();

        try {
            $result = $this->bookingService->create(
                $request->all(),
                'client',
                $client->client_slug,
                $client->client_slug
            );
        } catch (BookingAmountMismatchException $e) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_BAD_REQUEST, $e->getMessage(), []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Booking submitted', $result);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeClientBooking($booking);

        $booking->update($request->only([
            'special_requests', 'dietary_needs', 'additional_travelers',
            'specialRequests', 'dietaryNeeds', 'additionalTravelers',
        ]));

        $booking->load('tour');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking updated', $booking->toBookingArray());
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $this->authorizeClientBooking($booking);
        $booking->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking deleted', []);
    }

    protected function authorizeClientBooking(Booking $booking): void
    {
        if ($booking->client_slug !== request()->user()->client_slug) {
            abort(403, 'Booking not found');
        }
    }
}
