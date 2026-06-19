<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()->with('tour');

        if ($request->filled('client_slug')) {
            $query->where('client_slug', $request->client_slug);
        }

        $paginator = self::paginateQuery($request, $query->latest());

        return self::paginatedApiResponse('Bookings retrieved', $paginator, fn (Booking $b) => $b->toBookingArray());
    }

    public function show(Booking $booking): JsonResponse
    {
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

        $admin = request()->user();
        $clientSlug = $request->input('clientSlug') ?? $request->input('client_slug');

        if ($clientSlug && ! Client::query()->where('client_slug', $clientSlug)->exists()) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Client not found', []);
        }

        $result = $this->bookingService->create(
            $request->all(),
            'admin',
            $admin->admin_slug,
            $clientSlug
        );

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Booking created', $result);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $booking->update($request->only(['status', 'payment_status', 'special_requests', 'dietary_needs']));
        $booking->load('tour');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking updated', $booking->toBookingArray());
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $booking->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Booking deleted', []);
    }
}
