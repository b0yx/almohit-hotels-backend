<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Services\AuditService;
use App\Support\CompatResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['detail' => 'Authentication credentials were not provided.'], 401);
        }

        $pageSize = max(1, min(100, (int) ($request->query('page_size', $request->query('per_page', 20)))));

        $hotels = Hotel::query()
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))
            ->with(['amenities', 'images', 'reviews', 'policy', 'socialMedia', 'contacts', 'setupStatus', 'faqs'])
            ->withExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $user->id)])
            ->latest()
            ->paginate($pageSize);

        return response()->json(CompatResponse::page($hotels));
    }

    public function store(Request $request, int $hotel): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['detail' => 'Authentication credentials were not provided.'], 401);
        }

        $hotelModel = Hotel::query()->find($hotel);
        if (! $hotelModel) {
            return response()->json(['detail' => 'Hotel not found.'], 404);
        }

        $existing = Favorite::query()
            ->where('user_id', $user->id)
            ->where('hotel_id', $hotel)
            ->first();

        if ($existing) {
            return response()->json(['detail' => 'Hotel is already in favorites.'], 409);
        }

        $favorite = Favorite::query()->create([
            'user_id' => $user->id,
            'hotel_id' => $hotel,
        ]);

        $favorite->load('hotel');
        AuditService::log('created', 'favorite', $favorite);

        return response()->json(CompatResponse::favorite($favorite), 201);
    }

    public function destroy(Request $request, int $hotel): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['detail' => 'Authentication credentials were not provided.'], 401);
        }

        $favorite = Favorite::query()
            ->where('user_id', $user->id)
            ->where('hotel_id', $hotel)
            ->first();

        if (! $favorite) {
            return response()->json(['detail' => 'Favorite not found.'], 404);
        }

        AuditService::log('deleted', 'favorite', $favorite);
        $favorite->delete();

        return response()->json(null, 204);
    }
}
