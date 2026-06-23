<?php

namespace App\Http\Controllers\Api;

use App\Models\Hotel;
use App\Models\Review;
use App\Support\CompatResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends CrudController
{
    public function __construct()
    {
        parent::__construct(Review::class);
    }

    public function propertyReviews(Request $request, int $property): JsonResponse
    {
        $hotelExists = Hotel::query()->whereKey($property)->exists();
        if (! $hotelExists) {
            return response()->json(['detail' => 'Hotel not found.'], 404);
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'guest_name' => ['required', 'string', 'max:255'],
                'guest_email' => ['nullable', 'email'],
                'rating' => ['required', 'integer', 'min:1', 'max:5'],
                'title' => ['nullable', 'string', 'max:255'],
                'comment' => ['required', 'string'],
                'cleanliness' => ['nullable', 'integer', 'min:1', 'max:5'],
                'location' => ['nullable', 'integer', 'min:1', 'max:5'],
                'staff' => ['nullable', 'integer', 'min:1', 'max:5'],
                'comfort' => ['nullable', 'integer', 'min:1', 'max:5'],
                'value_for_money' => ['nullable', 'integer', 'min:1', 'max:5'],
            ]);
            $review = Review::query()->create(array_merge($data, [
                'hotel_id' => $property,
                'user_id' => $request->user()?->id,
                'is_active' => true,
            ]));

            return response()->json(CompatResponse::review($review), 201);
        }

        $page = Review::query()->where('hotel_id', $property)->where('is_active', true)->latest('id')->paginate(20);

        return response()->json(CompatResponse::page($page));
    }

    public function summary(int $property): JsonResponse
    {
        $hotelExists = Hotel::query()->whereKey($property)->exists();
        if (! $hotelExists) {
            return response()->json(['detail' => 'Hotel not found.'], 404);
        }

        $stats = Review::query()
            ->where('hotel_id', $property)
            ->where('is_active', true)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('ROUND(AVG(rating), 1) as avg_rating')
            ->selectRaw('ROUND(AVG(cleanliness), 1) as avg_cleanliness')
            ->selectRaw('ROUND(AVG(location), 1) as avg_location')
            ->selectRaw('ROUND(AVG(staff), 1) as avg_staff')
            ->selectRaw('ROUND(AVG(comfort), 1) as avg_comfort')
            ->selectRaw('ROUND(AVG(value_for_money), 1) as avg_value_for_money')
            ->first();

        $total = (int) ($stats?->total ?? 0);
        $breakdown = Review::query()
            ->where('hotel_id', $property)
            ->where('is_active', true)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $ratingBreakdown = [];
        foreach ([5, 4, 3, 2, 1] as $rating) {
            $ratingBreakdown[(string) $rating] = (int) ($breakdown[$rating] ?? 0);
        }

        return response()->json([
            'average_rating' => $total ? (float) ($stats?->avg_rating ?? 0) : null,
            'total_reviews' => $total,
            'rating_breakdown' => $ratingBreakdown,
            'category_averages' => [
                'cleanliness' => $total ? (float) ($stats?->avg_cleanliness ?? 0) : null,
                'location' => $total ? (float) ($stats?->avg_location ?? 0) : null,
                'staff' => $total ? (float) ($stats?->avg_staff ?? 0) : null,
                'comfort' => $total ? (float) ($stats?->avg_comfort ?? 0) : null,
                'value_for_money' => $total ? (float) ($stats?->avg_value_for_money ?? 0) : null,
            ],
        ]);
    }
}
