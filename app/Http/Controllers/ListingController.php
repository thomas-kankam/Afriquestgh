<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    use \App\Traits\ListingMapper;

    public function index(Request $request): JsonResponse
    {
        $query = Tour::query()->published();
        if ($request->filled('featured')) {
            $query->where('featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        if ($request->filled('price_amount')) {
            $query->where('price_amount', '>=', $request->price_amount);
        }

        // sort_by_created_at asc or desc
        if ($request->filled('sort_by')) {
            $query->orderBy('created_at', $request->sort_by);
        }

        $paginator = self::paginateQuery($request, $query->latest()->orderBy('created_at', 'desc'));

        return self::paginatedApiResponse('Listings retrieved', $paginator, fn(Tour $tour) => $tour->toListingArray());
    }

    public function show(Tour $listing): JsonResponse
    {
        if ($listing->status !== 'published') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Listing not found', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing retrieved', $listing->toListingArray());
    }
}
