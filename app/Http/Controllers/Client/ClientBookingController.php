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

        return self::paginatedApiResponse('Bookings retrieved', $paginator, fn(Booking $booking) => $booking->toBookingArray());
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
            'bookingType' => 'required|in:group,individual',
            'tourSlug' => 'required|string|exists:tours,tour_slug',
            'selectedDate' => 'required|date',
            'travelers' => 'required|integer|min:1',
            'paymentMode' => 'required|in:online,onsite',
            'leadTraveler' => 'required|array|min:1',
            'leadTraveler.email' => 'required|email',
            'groupDetails' => 'nullable|array',
            'amount' => 'required|numeric|min:0',
            'specialRequests' => 'nullable|string',
            'dietaryNeeds' => 'nullable|string',
            'additionalTravelers' => 'nullable|array',
            'clientSlug' => 'nullable|string|exists:clients,client_slug',
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

        if ($booking->payment_mode === 'online') {
            return self::apiResponse(
                true,
                'Action Unsuccessful',
                (string) self::API_FAIL,
                'Online bookings cannot be updated. Please create a new booking instead.',
                []
            );
        }

        $data = $request->validate([
            'special_requests' => 'nullable|string',
            'dietary_needs' => 'nullable|string',
            'additional_travelers' => 'nullable|array',
            'selected_date' => 'sometimes|date',
            'selectedDate' => 'sometimes|date',
            'travelers' => 'sometimes|integer|min:1',
        ]);

        $booking->update(array_filter([
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
