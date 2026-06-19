<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Traits\ListingMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminListingController extends Controller
{
    use ListingMapper;

    public function index(Request $request): JsonResponse
    {
        $query = Tour::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = self::paginateQuery($request, $query->latest());

        return self::paginatedApiResponse('Listings retrieved', $paginator, fn (Tour $tour) => $tour->toListingArray());
    }

    public function show(Tour $listing): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing retrieved', $listing->toListingArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => 'nullable|string|max:255|unique:tours,tour_slug',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive,expired,live',
        ]);

        $attrs = self::mapListingPayloadToAttributes($request->all(), request()->user()->admin_slug);
        $listing = Tour::create($attrs);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Listing created', $listing->toListingArray());
    }

    public function update(Request $request, Tour $listing): JsonResponse
    {
        $attrs = self::mapListingPayloadToAttributes($request->all(), $listing->created_by_admin_slug);
        unset($attrs['tour_slug']);
        $listing->update($attrs);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing updated', $listing->fresh()->toListingArray());
    }

    public function destroy(Tour $listing): JsonResponse
    {
        $listing->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing deleted', []);
    }

    public function updateStatus(Request $request, Tour $listing): JsonResponse
    {
        $request->validate(['status' => 'required|in:active,inactive,expired']);

        $listing->update(['status' => $request->status]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing status updated', $listing->fresh()->toListingArray());
    }
}
