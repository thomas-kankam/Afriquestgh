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
        $query = Tour::query()->active();

        if ($request->filled('featured')) {
            $query->where('featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('country_code')) {
            $query->where('country_code', $request->country_code);
        }

        $listings = $query->latest()->get()->map->toListingArray();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listings retrieved', $listings->all());
    }

    public function show(Tour $listing): JsonResponse
    {
        if ($listing->status !== 'active') {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Listing not found', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Listing retrieved', $listing->toListingArray());
    }
}
