<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Traits\ListingMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorListingController extends Controller
{
    use ListingMapper;

    public function index(Request $request): JsonResponse
    {
        $operator = request()->user();
        $query = Tour::query()->where('operator_slug', $operator->operator_slug);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = self::paginateQuery($request, $query->latest());

        return self::paginatedApiResponse('Tours retrieved', $paginator, fn (Tour $tour) => $tour->toListingArray());
    }

    public function show(Tour $listing): JsonResponse
    {
        $this->authorizeOperatorTour($listing);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Tour retrieved', $listing->toListingArray());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'nullable|string|max:255|unique:tours,tour_slug',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive,expired,live',
        ]);

        $operator = request()->user();
        $attrs = self::mapListingPayloadToAttributes($request->all(), null, $operator->operator_slug);
        $listing = Tour::create($attrs);

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Tour created', $listing->toListingArray());
    }

    public function update(Request $request, Tour $listing): JsonResponse
    {
        $this->authorizeOperatorTour($listing);

        $attrs = self::mapListingPayloadToAttributes($request->all(), null, $listing->operator_slug);
        unset($attrs['tour_slug'], $attrs['operator_slug']);
        $listing->update($attrs);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Tour updated', $listing->fresh()->toListingArray());
    }

    public function destroy(Tour $listing): JsonResponse
    {
        $this->authorizeOperatorTour($listing);
        $listing->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Tour deleted', []);
    }

    protected function authorizeOperatorTour(Tour $tour): void
    {
        if ($tour->operator_slug !== request()->user()->operator_slug) {
            abort(403, 'Tour not found');
        }
    }
}
