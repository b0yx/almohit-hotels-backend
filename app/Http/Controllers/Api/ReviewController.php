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
        $reviews = Review::query()->where('hotel_id', $property)->where('is_active', true);
        $total = (clone $reviews)->count();
        $breakdown = [];
        foreach ([5, 4, 3, 2, 1] as $rating) {
            $breakdown[(string) $rating] = (clone $reviews)->where('rating', $rating)->count();
        }

        return response()->json([
            'average_rating' => $total ? round((float) (clone $reviews)->avg('rating'), 1) : null,
            'total_reviews' => $total,
            'rating_breakdown' => $breakdown,
            'category_averages' => [
                'cleanliness' => $total ? round((float) (clone $reviews)->avg('cleanliness'), 1) : null,
                'location' => $total ? round((float) (clone $reviews)->avg('location'), 1) : null,
                'staff' => $total ? round((float) (clone $reviews)->avg('staff'), 1) : null,
                'comfort' => $total ? round((float) (clone $reviews)->avg('comfort'), 1) : null,
                'value_for_money' => $total ? round((float) (clone $reviews)->avg('value_for_money'), 1) : null,
            ],
        ]);
    }
}
