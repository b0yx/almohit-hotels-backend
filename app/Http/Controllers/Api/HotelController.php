<?php

namespace App\Http\Controllers\Api;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Support\CompatResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HotelController extends CrudController
{
    public function __construct()
    {
        parent::__construct(Hotel::class);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Hotel::query()->with(['amenities', 'images']);
        $user = $request->user();
        $publicHotel = $request->attributes->get('public_hotel');

        if ($publicHotel && ! ($user?->isAdmin())) {
            $query->whereKey($publicHotel->id);
        } elseif (! $user || (! $user->isAdmin() && ! $user->isStaffRole())) {
            $query->where('is_active', true)->where('publishing_status', 'published');
        } elseif ($user->isStaffRole() && ! $user->isAdmin()) {
            $query->whereHas('assignedStaff', fn ($q) => $q->whereKey($user->id));
        }

        foreach (['country', 'city', 'property_type', 'stars'] as $filter) {
            if ($request->query($filter)) {
                $query->where($filter, $request->query($filter));
            }
        }

        return response()->json(CompatResponse::page($query->latest('id')->paginate(20)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->normalizeInput($request->all());
        $data['slug'] = $data['slug'] ?? Str::slug($data['name'] ?? Str::random(8));
        $data['publishing_status'] = $data['publishing_status'] ?? 'draft';

        $hotel = Hotel::query()->create($data);
        $this->syncManyToMany($hotel, $request);

        return response()->json(CompatResponse::hotel($hotel->fresh(['amenities', 'images'])), 201);
    }

    public function publish(int $id): JsonResponse
    {
        $hotel = Hotel::query()->findOrFail($id);
        $hotel->forceFill(['publishing_status' => 'published', 'is_active' => true, 'published_at' => $hotel->published_at ?: now()])->save();
        $hotel->load(['amenities', 'images']);

        return response()->json(CompatResponse::hotel($hotel));
    }

    public function unpublish(int $id): JsonResponse
    {
        $hotel = Hotel::query()->findOrFail($id);
        $hotel->forceFill(['publishing_status' => 'draft', 'published_at' => null])->save();
        $hotel->load(['amenities', 'images']);

        return response()->json(CompatResponse::hotel($hotel));
    }

    public function archive(int $id): JsonResponse
    {
        $hotel = Hotel::query()->findOrFail($id);
        $hotel->forceFill(['publishing_status' => 'archived', 'is_active' => false, 'published_at' => null])->save();
        $hotel->load(['amenities', 'images']);

        return response()->json(CompatResponse::hotel($hotel));
    }

    public function unarchive(int $id): JsonResponse
    {
        $hotel = Hotel::query()->findOrFail($id);
        $hotel->forceFill(['publishing_status' => 'draft', 'is_active' => true])->save();
        $hotel->load(['amenities', 'images']);

        return response()->json(CompatResponse::hotel($hotel));
    }

    public function readiness(int $id): JsonResponse
    {
        $hotel = Hotel::query()->with(['images', 'roomTypes'])->findOrFail($id);
        $errors = CompatResponse::computeReadinessErrors($hotel);

        if ($hotel->roomTypes->where('is_active', true)->count() === 0) {
            $errors[] = 'At least one active room type is required.';
        }
        if ($hotel->images->where('is_active', true)->count() === 0) {
            $errors[] = 'At least one active property image is required.';
        }

        return response()->json([
            'is_ready_to_publish' => empty($errors),
            'errors' => $errors,
        ]);
    }

    public function setupStatus(int $id): JsonResponse
    {
        $hotel = Hotel::query()->with('setupStatus')->findOrFail($id);
        $setup = $hotel->setupStatus;

        return response()->json([
            'completion_percentage' => $setup?->completion_percentage ?? 0,
            'last_completed_step' => $setup?->last_completed_step ?? 1,
            'autosaved_at' => $setup?->autosaved_at?->toJSON(),
        ]);
    }

    public function autosave(Request $request, int $id): JsonResponse
    {
        $this->update($request, $id);
        $hotel = Hotel::query()->with(['policy', 'contacts', 'socialMedia', 'setupStatus', 'images', 'amenities', 'reviews'])->findOrFail($id);
        $setup = $hotel->setupStatus;

        if ($setup && $request->has('last_completed_step')) {
            $setup->forceFill([
                'last_completed_step' => (int) $request->input('last_completed_step'),
                'autosaved_at' => now(),
                'completion_percentage' => min(100, (int) (($request->input('last_completed_step', 1) / 10) * 100)),
            ])->save();
        }

        return response()->json([
            'property' => CompatResponse::hotel($hotel),
            'setup' => [
                'completion_percentage' => $setup?->completion_percentage ?? 0,
                'last_completed_step' => $setup?->last_completed_step ?? 1,
                'autosaved_at' => $setup?->autosaved_at?->toJSON(),
            ],
        ]);
    }

    public function workspace(int $id): JsonResponse
    {
        $hotel = Hotel::query()->with(['amenities', 'images', 'reviews', 'policy', 'socialMedia', 'contacts', 'setupStatus'])->findOrFail($id);

        return response()->json([
            'property' => CompatResponse::hotel($hotel),
            'setup' => ['completion_percentage' => 0, 'last_completed_step' => 1, 'autosaved_at' => null],
            'recent_activity' => [],
            'missing_tasks' => [],
            'recommended_next_action' => null,
        ]);
    }

    public function roomsSearch(Request $request, int $id): JsonResponse
    {
        $rooms = RoomType::where('hotel_id', $id)->where('is_active', true)->get()->map(fn ($room) => [
            'room_type_id' => $room->id,
            'name' => $room->name,
            'description' => $room->description,
            'max_adults' => $room->max_adults,
            'max_children' => $room->max_children,
            'total_units' => $room->total_units,
            'base_price' => (string) $room->base_price,
            'currency' => $room->currency,
            'extra_bed_allowed' => (bool) $room->extra_bed_allowed,
            'extra_bed_price' => (string) $room->extra_bed_price,
            'breakfast_included' => (bool) $room->breakfast_included,
            'cover_image_url' => null,
        ]);

        return response()->json(['property_id' => $id, 'rooms' => $rooms]);
    }

    public function availability(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::query()->with(['roomTypes' => fn ($q) => $q->where('is_active', true)])->findOrFail($id);
        $units = $hotel->roomTypes->map(fn ($room) => [
            'id' => $room->id,
            'name' => $room->name,
            'available_units' => $room->total_units,
            'is_available' => $room->total_units > 0,
            'max_adults' => $room->max_adults,
            'max_children' => $room->max_children,
            'base_price' => (string) $room->base_price,
            'currency' => $room->currency,
        ]);

        return response()->json([
            'property_id' => $hotel->id,
            'property' => $hotel->id,
            'property_name' => $hotel->name,
            'property_type' => $hotel->property_type,
            'check_in' => $request->query('check_in'),
            'check_out' => $request->query('check_out'),
            'is_available' => $units->contains('is_available', true),
            'available_rooms' => $units->where('is_available', true)->values(),
            'units' => $units,
        ]);
    }

    public function rates(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::query()->findOrFail($id);

        return response()->json([
            'property_id' => $hotel->id,
            'property_name' => $hotel->name,
            'rates' => $hotel->roomTypes()->with(['prices', 'images'])->get()->map(fn ($room) => [
                'room_type_id' => $room->id,
                'room_name' => $room->name,
                'base_price' => (string) $room->base_price,
                'currency' => $room->currency,
                'max_adults' => $room->max_adults,
                'max_children' => $room->max_children,
                'total_units' => $room->total_units,
                'is_active' => (bool) $room->is_active,
                'seasonal_prices' => $room->prices->map(fn ($price) => CompatResponse::item($price)),
            ]),
        ]);
    }

    public function publicContext(Request $request): JsonResponse
    {
        $hotel = $request->attributes->get('public_hotel');

        return response()->json([
            'subdomain' => $request->attributes->get('public_hotel_subdomain'),
            'status' => $request->attributes->get('public_hotel_status'),
            'property' => $hotel ? CompatResponse::hotel($hotel->loadMissing('images')) : null,
        ]);
    }
}
