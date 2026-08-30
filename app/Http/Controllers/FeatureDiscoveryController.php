<?php

namespace App\Http\Controllers;

use App\Support\Discovery\FeatureRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FeatureDiscoveryController extends Controller
{
    public function search(Request $request, FeatureRegistry $registry): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $user = auth()->user();
        $scope = $registry->scopeForUser();
        $limit = min((int) $request->query('limit', config('feature_registry.results_limit', 24)), 50);

        $cacheKey = 'feature-discovery:'.hash('sha256', implode('|', [
            $scope,
            $query,
            $user?->id,
            $limit,
        ]));

        $results = Cache::remember($cacheKey, now()->addMinutes(5), fn () => $registry->search($query, $scope, $limit));

        return response()->json(['results' => $results]);
    }
}
