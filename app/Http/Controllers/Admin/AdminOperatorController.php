<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOperatorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Operator::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse(
            'Operators retrieved',
            $paginator,
            fn(Operator $operator) => $this->toAdminOperatorArray($operator)
        );
    }

    public function show(Operator $operator): JsonResponse
    {
        $operator->loadCount(['tours', 'bookings']);

        return self::apiResponse(
            false,
            'Action Successful',
            (string) self::API_SUCCESS,
            'Operator retrieved',
            $this->toAdminOperatorArray($operator)
        );
    }

    protected function toAdminOperatorArray(Operator $operator): array
    {
        $data = $operator->toOperatorArray();

        return $data;
    }
}
