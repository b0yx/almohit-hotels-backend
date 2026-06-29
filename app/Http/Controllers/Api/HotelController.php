<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RoomResource;
use App\Models\Hotel;
use App\Models\HotelPolicy;
use App\Models\RoomType;
use App\Services\AuditService;
use App\Services\FaqService;
use App\Support\CompatResponse;
use App\Support\LocalizedMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HotelController extends CrudController
{
    public function __construct()
    {
        parent::__construct(Hotel::class);
    }

    private function authorizeStaffHotelAccess(Request $request, int $hotelId): void
    {
        $user = $request->user();
        if ($user && $user->isStaffRole() && ! $user->isAdmin()) {
            $hasAccess = Hotel::query()->whereKey($hotelId)->whereHas('assignedStaff', fn ($q) => $q->whereKey($user->id))->exists();
            if (! $hasAccess) {
                abort(403, 'You do not have permission to access this hotel.');
            }
        }
    }

    private function staffOrAdminError(Request $request, string $action, ?int $hotelId = null): ?JsonResponse
    {
        if ($error = $this->checkAuthorization($request, $action, $hotelId)) {
            return $error;
        }

        if ($hotelId !== null) {
            $this->authorizeStaffHotelAccess($request, $hotelId);
        }

        return null;
    }

    private function publicAccessError(Request $request, int $hotelId): ?JsonResponse
    {
        $user = $request->user();
        if ($user && ($user->isAdmin() || $user->isStaffRole())) {
            $this->authorizeStaffHotelAccess($request, $hotelId);

            return null;
        }

        $isVisible = Hotel::query()
            ->whereKey($hotelId)
            ->where('is_active', true)
            ->where('publishing_status', 'published')
            ->exists();

        if (! $isVisible) {
            return response()->json(['detail' => 'Hotel not found.'], 404);
        }

        return null;
    }

    public function show(int $id): JsonResponse
    {
        $request = request();
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->isStaffRole())) {
            $query = Hotel::query()
                ->with(['amenities', 'images', 'reviews', 'policy', 'socialMedia', 'contacts', 'setupStatus', 'faqs'])
                ->whereKey($id)
                ->where('is_active', true)
                ->where('publishing_status', 'published');

            if ($user) {
                $query->withExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $user->id)]);
            }

            $hotel = $query->first();

            if ($hotel) {
                return response()->json(CompatResponse::hotel($hotel));
            }

            return response()->json(['detail' => 'Hotel not found.'], 404);
        }

        $this->authorizeStaffHotelAccess($request, $id);

        $query = Hotel::query()->with(['amenities', 'images', 'reviews', 'policy', 'socialMedia', 'contacts', 'setupStatus', 'faqs']);
        if ($user) {
            $query->withExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $user->id)]);
        }

        return response()->json(CompatResponse::hotel($query->findOrFail($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'update', $id)) {
            return $error;
        }

        $validated = $this->validateHotelPayload($request, $id);
        $hotel = Hotel::query()->findOrFail($id);
        $data = $this->normalizeInput($validated);
        $data = LocalizedMapper::mapInputForSave(Hotel::class, $data, null, true);
        if (array_key_exists('video_url', $data) && $data['video_url'] === null) {
            $data['video_url'] = '';
        }
        $coverImageId = $data['cover_image_id'] ?? null;
        unset($data['cover_image_id']);
        $nested = $this->extractNestedHotelPayload($data);
        $faqsData = array_key_exists('faqs', $data) ? $data['faqs'] : null;
        unset($data['faqs']);
        $changes = AuditService::changes($hotel, $data);
        $hotel->fill($data)->save();
        $this->syncManyToMany($hotel, $request);
        $this->syncNestedHotelRelations($hotel, $nested);
        $this->syncCoverImage($hotel, $coverImageId);
        FaqService::syncFaqs($hotel, $faqsData);

        $fresh = $hotel->fresh(['amenities', 'images', 'policy', 'socialMedia', 'contacts', 'setupStatus', 'faqs']);
        AuditService::log('updated', 'hotel', $fresh, $changes);

        return response()->json(CompatResponse::hotel($fresh));
    }

    public function destroy(int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError(request(), 'destroy', $id)) {
            return $error;
        }

        return parent::destroy($id);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Hotel::query()->with(['amenities', 'images', 'policy', 'faqs']);
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

        if ($user) {
            $query->withExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $user->id)]);
        }

        return response()->json(CompatResponse::page($query->latest('id')->paginate($this->pageSize($request))));
    }

    public function store(Request $request): JsonResponse
    {
        if ($error = $this->checkAuthorization($request, 'store')) {
            return $error;
        }

        $validated = $this->validateHotelPayload($request);
        $data = $this->normalizeInput($validated);
        $data = LocalizedMapper::mapInputForSave(Hotel::class, $data, null, false);
        if (array_key_exists('video_url', $data) && $data['video_url'] === null) {
            $data['video_url'] = '';
        }
        $coverImageId = $data['cover_image_id'] ?? null;
        unset($data['cover_image_id']);
        $nested = $this->extractNestedHotelPayload($data);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name'] ?? $data['name_ar'] ?? Str::random(8));
        $data['publishing_status'] = $data['publishing_status'] ?? 'draft';

        $faqsData = array_key_exists('faqs', $data) ? $data['faqs'] : null;
        unset($data['faqs']);
        $hotel = Hotel::query()->create($data);
        $this->syncManyToMany($hotel, $request);
        $this->syncNestedHotelRelations($hotel, $nested);
        $this->syncCoverImage($hotel, $coverImageId);
        FaqService::syncFaqs($hotel, $faqsData);

        $fresh = $hotel->fresh(['amenities', 'images', 'policy', 'socialMedia', 'contacts', 'setupStatus', 'faqs']);
        AuditService::log('created', 'hotel', $fresh);

        return response()->json(CompatResponse::hotel($fresh), 201);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'update', $id)) {
            return $error;
        }

        $hotel = Hotel::query()->findOrFail($id);
        $oldStatus = $hotel->publishing_status;
        $hotel->forceFill(['publishing_status' => 'published', 'is_active' => true, 'published_at' => $hotel->published_at ?: now()])->save();
        $hotel->load(['amenities', 'images']);

        AuditService::log('published', 'hotel', $hotel, ['publishing_status' => ['old' => $oldStatus, 'new' => 'published']]);

        return response()->json(CompatResponse::hotel($hotel));
    }

    public function unpublish(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'update', $id)) {
            return $error;
        }

        $hotel = Hotel::query()->findOrFail($id);
        $oldStatus = $hotel->publishing_status;
        $hotel->forceFill(['publishing_status' => 'draft', 'published_at' => null])->save();
        $hotel->load(['amenities', 'images']);

        AuditService::log('unpublished', 'hotel', $hotel, ['publishing_status' => ['old' => $oldStatus, 'new' => 'draft']]);

        return response()->json(CompatResponse::hotel($hotel));
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'update', $id)) {
            return $error;
        }

        $hotel = Hotel::query()->findOrFail($id);
        $oldStatus = $hotel->publishing_status;
        $hotel->forceFill(['publishing_status' => 'archived', 'is_active' => false, 'published_at' => null])->save();
        $hotel->load(['amenities', 'images']);

        AuditService::log('archived', 'hotel', $hotel, ['publishing_status' => ['old' => $oldStatus, 'new' => 'archived']]);

        return response()->json(CompatResponse::hotel($hotel));
    }

    public function unarchive(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'update', $id)) {
            return $error;
        }

        $hotel = Hotel::query()->findOrFail($id);
        $oldStatus = $hotel->publishing_status;
        $hotel->forceFill(['publishing_status' => 'draft', 'is_active' => true])->save();
        $hotel->load(['amenities', 'images']);

        AuditService::log('unarchived', 'hotel', $hotel, ['publishing_status' => ['old' => $oldStatus, 'new' => 'draft']]);

        return response()->json(CompatResponse::hotel($hotel));
    }

    public function readiness(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'show', $id)) {
            return $error;
        }

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

    public function setupStatus(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'show', $id)) {
            return $error;
        }

        $hotel = Hotel::query()->with('setupStatus')->findOrFail($id);
        $setup = $hotel->setupStatus;

        return response()->json([
            'completion_percentage' => $setup?->completion_percentage ?? 0,
            'last_completed_step' => $setup?->last_completed_step ?? 1,
            'autosaved_at' => $setup?->autosaved_at?->toJSON(),
            'status' => $hotel->publishing_status,
        ]);
    }

    private function validateHotelPayload(Request $request, ?int $hotelId = null): array
    {
        $isUpdate = $hotelId !== null;
        $subdomainRule = Rule::unique('hotels', 'subdomain');
        if ($hotelId) {
            $subdomainRule = $subdomainRule->ignore($hotelId);
        }

        $requiredString = fn (int $max) => $isUpdate
            ? ['sometimes', 'required', 'string', "max:{$max}"]
            : ['required', 'string', "max:{$max}"];

        $requiredStars = $isUpdate
            ? ['sometimes', 'required', 'integer', 'min:1', 'max:5']
            : ['required', 'integer', 'min:1', 'max:5'];

        $policyTimeRule = ['nullable', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'];

        return $request->validate([
            'name' => $requiredString(255),
            'name_ar' => ['nullable', 'string', 'max:255'],
            'property_type' => $requiredString(20),
            'country' => $requiredString(100),
            'city' => $requiredString(100),
            'address' => $requiredString(500),
            'stars' => $requiredStars,
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'short_description_ar' => ['nullable', 'string', 'max:300'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_title_ar' => ['nullable', 'string', 'max:60'],
            'meta_description_ar' => ['nullable', 'string', 'max:160'],
            'is_active' => ['sometimes', 'boolean'],
            'subdomain' => ['nullable', 'string', 'max:63', $subdomainRule],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'amenity_ids' => ['sometimes', 'array'],
            'amenity_ids.*' => ['integer'],
            'policy' => ['sometimes', 'array'],
            'policy.cancellation_policy' => ['nullable', 'string'],
            'policy.children_policy' => ['nullable', 'string'],
            'policy.pet_policy' => ['nullable', 'string'],
            'policy.smoking_policy' => ['nullable', 'string'],
            'policy.extra_bed_policy' => ['nullable', 'string'],
            'policy.cancellation_policy_ar' => ['nullable', 'string'],
            'policy.children_policy_ar' => ['nullable', 'string'],
            'policy.pet_policy_ar' => ['nullable', 'string'],
            'policy.smoking_policy_ar' => ['nullable', 'string'],
            'policy.extra_bed_policy_ar' => ['nullable', 'string'],
            'policy.important_notes' => ['nullable', 'string'],
            'policy.check_in_from' => $policyTimeRule,
            'policy.check_in_to' => $policyTimeRule,
            'policy.check_out_from' => $policyTimeRule,
            'policy.check_out_to' => $policyTimeRule,
            'social_media' => ['sometimes', 'array'],
            'contacts' => ['sometimes', 'array'],
            'cover_image_id' => ['sometimes', 'nullable', 'integer'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'faqs' => ['sometimes', 'nullable', 'array', 'max:50'],
            'faqs.*.id' => ['sometimes', 'nullable', 'integer', 'exists:faqs,id'],
            'faqs.*.question' => ['required_with:faqs', 'string', 'max:255'],
            'faqs.*.answer' => ['required_with:faqs', 'string'],
            'faqs.*.sort_order' => ['nullable', 'integer'],
            'faqs.*.is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function extractNestedHotelPayload(array &$data): array
    {
        $nested = [];
        foreach (['policy', 'social_media', 'contacts'] as $key) {
            if (array_key_exists($key, $data)) {
                $nested[$key] = is_array($data[$key]) ? $data[$key] : [];
                unset($data[$key]);
            }
        }

        return $nested;
    }

    private function syncNestedHotelRelations(Hotel $hotel, array $nested): void
    {
        if (array_key_exists('policy', $nested)) {
            $isPolicyUpdate = $hotel->policy()->exists();
            $hotel->policy()->updateOrCreate(
                ['hotel_id' => $hotel->id],
                $this->filterPolicyPayload($nested['policy'], $isPolicyUpdate)
            );
        }

        if (array_key_exists('social_media', $nested)) {
            $hotel->socialMedia()->updateOrCreate(
                ['hotel_id' => $hotel->id],
                $this->filterSocialMediaPayload($nested['social_media'])
            );
        }

        if (array_key_exists('contacts', $nested)) {
            $hotel->contacts()->updateOrCreate(
                ['hotel_id' => $hotel->id],
                $this->filterContactsPayload($nested['contacts'])
            );
        }
    }

    private function filterPolicyPayload(array $policy, bool $isUpdate = false): array
    {
        $allowed = [
            'check_in_from',
            'check_in_to',
            'check_out_from',
            'check_out_to',
            'cancellation_policy',
            'children_policy',
            'pet_policy',
            'smoking_policy',
            'extra_bed_policy',
            'cancellation_policy_ar',
            'children_policy_ar',
            'pet_policy_ar',
            'smoking_policy_ar',
            'extra_bed_policy_ar',
            'important_notes',
        ];

        $policy = LocalizedMapper::mapInputForSave(HotelPolicy::class, $policy, null, $isUpdate);

        return collect($policy)->only($allowed)->map(function ($value, $key) {
            if (in_array($key, ['check_in_from', 'check_in_to', 'check_out_from', 'check_out_to'], true)) {
                return $this->normalizePolicyTime($value);
            }

            if (str_ends_with((string) $key, '_ar')) {
                return $value === '' || $value === null ? null : (string) $value;
            }

            return $this->blankString($value);
        })->all();
    }

    private function filterSocialMediaPayload(array $socialMedia): array
    {
        $allowed = [
            'facebook_url',
            'instagram_url',
            'tiktok_url',
            'twitter_url',
            'youtube_url',
            'linkedin_url',
            'booking_com_url',
            'agoda_url',
            'airbnb_url',
            'expedia_url',
            'whatsapp_number',
            'telegram_username',
        ];

        return collect($socialMedia)
            ->only($allowed)
            ->map(fn ($value) => $this->blankString($value))
            ->all();
    }

    private function filterContactsPayload(array $contacts): array
    {
        $allowed = [
            'primary_contact_person',
            'contact_position',
            'emergency_contact_number',
        ];

        return collect($contacts)
            ->only($allowed)
            ->map(fn ($value) => $this->blankString($value))
            ->all();
    }

    private function blankString(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    private function normalizePolicyTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parts = explode(':', (string) $value);

        return sprintf('%02d:%02d:00', (int) $parts[0], (int) $parts[1]);
    }

    private function syncCoverImage(Hotel $hotel, mixed $coverImageId): void
    {
        if ($coverImageId === null || $coverImageId === '') {
            return;
        }

        $imageId = (int) $coverImageId;
        if ($imageId <= 0) {
            return;
        }

        $image = $hotel->images()->whereKey($imageId)->first();
        if (! $image) {
            return;
        }

        $hotel->images()->update(['is_cover' => false]);
        $image->refresh()->forceFill(['is_cover' => true])->save();
    }

    public function autosave(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'update', $id)) {
            return $error;
        }

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

    public function workspace(Request $request, int $id): JsonResponse
    {
        if ($error = $this->staffOrAdminError($request, 'show', $id)) {
            return $error;
        }

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
        if ($error = $this->publicAccessError($request, $id)) {
            return $error;
        }

        $rooms = RoomType::where('hotel_id', $id)
            ->where('is_active', true)
            ->get()
            ->map(fn (RoomType $room) => $this->mapGuestRoomSearchRow($room));

        return response()->json(['property_id' => $id, 'rooms' => $rooms]);
    }

    private function mapGuestRoomSearchRow(RoomType $room): array
    {
        $data = [
            'room_type_id' => $room->id,
            'name' => $room->name,
            'name_ar' => $room->name_ar,
            'description' => $room->description,
            'description_ar' => $room->description_ar,
            'max_adults' => $room->max_adults,
            'max_children' => $room->max_children,
            'total_units' => $room->total_units,
            'base_price' => (string) $room->base_price,
            'currency' => $room->currency,
            'extra_bed_allowed' => (bool) $room->extra_bed_allowed,
            'extra_bed_price' => (string) $room->extra_bed_price,
            'breakfast_included' => (bool) $room->breakfast_included,
            'cover_image_url' => null,
        ];

        $data = LocalizedMapper::mapOutput($room, $data);
        unset($data['name_ar'], $data['description_ar']);

        return $data;
    }

    public function availability(Request $request, int $id): JsonResponse
    {
        if ($error = $this->publicAccessError($request, $id)) {
            return $error;
        }

        $hotel = Hotel::query()->with(['roomTypes' => fn ($q) => $q->where('is_active', true)])->findOrFail($id);
        $units = $hotel->roomTypes->map(function (RoomType $room) {
            $searchRow = $this->mapGuestRoomSearchRow($room);

            return [
                'id' => $room->id,
                'name' => $searchRow['name'],
                'available_units' => $room->total_units,
                'is_available' => $room->total_units > 0,
                'max_adults' => $room->max_adults,
                'max_children' => $room->max_children,
                'base_price' => (string) $room->base_price,
                'currency' => $room->currency,
            ];
        });

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
        if ($error = $this->publicAccessError($request, $id)) {
            return $error;
        }

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

    public function publicRooms(Request $request, string $property): JsonResponse
    {
        $hotel = Hotel::query()
            ->where('publishing_status', 'published')
            ->where('is_active', true)
            ->where(function ($q) use ($property) {
                if (is_numeric($property)) {
                    $q->where('id', (int) $property);
                } else {
                    $q->where('slug', $property);
                }
            })
            ->first();

        if (! $hotel) {
            return response()->json(['detail' => 'Hotel not found.'], 404);
        }

        $rooms = RoomType::query()
            ->where('hotel_id', $hotel->id)
            ->where('is_active', true)
            ->where('total_units', '>', 0)
            ->with(['images' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return response()->json(RoomResource::collection($rooms)->resolve($request));
    }

    private function pageSize(Request $request): int
    {
        $perPage = $request->query('per_page', $request->query('page_size', 20));

        return max(1, min(100, (int) $perPage));
    }
}
