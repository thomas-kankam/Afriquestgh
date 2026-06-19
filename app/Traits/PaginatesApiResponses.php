<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait PaginatesApiResponses
{
    protected static function paginateQuery(Request $request, Builder $query, int $defaultPerPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min((int) $request->input('per_page', $defaultPerPage), 100));
        $page = max(1, (int) $request->input('page', 1));

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    protected static function paginatedApiResponse(
        string $reason,
        LengthAwarePaginator $paginator,
        callable $transform,
        int|string $statusCode = self::API_SUCCESS,
    ): JsonResponse {
        return self::apiResponse(false, 'Action Successful', (string) $statusCode, $reason, [
            'items' => collect($paginator->items())->map($transform)->values()->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
