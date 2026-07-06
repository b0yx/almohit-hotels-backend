<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BedType;
use App\Models\BookingInquiry;
use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PublicHotelCache;
use App\Support\CompatResponse;
use App\Support\LocalizedMapper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrudController extends Controller
{
    private const string ADMIN_ONLY = 'admin_only';

    private const string STAFF_OR_ADMIN = 'staff_or_admin';

    private const string READ_PUBLIC = 'read_public';

    private array $accessMap = [];

    public function __construct(protected string $modelClass)
    {
        $this->accessMap = [
            User::class => self::ADMIN_ONLY,
            AuditLog::class => self::ADMIN_ONLY,
            Hotel::class => self::STAFF_OR_ADMIN,
            Facility::class => self::READ_PUBLIC,
            FacilityCategory::class => self::READ_PUBLIC,
            ContactMessage::class => self::STAFF_OR_ADMIN,
            BookingInquiry::class => self::STAFF_OR_ADMIN,
            Review::class => self::STAFF_OR_ADMIN,
        ];
    }

    protected function checkAuthorization(Request $request, string $action, ?int $resourceId = null): ?JsonResponse
    {
        $user = $request->user();
        $level = $this->accessMap[$this->modelClass] ?? self::STAFF_OR_ADMIN;

        if ($level === self::READ_PUBLIC && in_array($action, ['index', 'show'], true)) {
            return null;
        }

        if (! $user) {
            return response()->json(['detail' => 'Authentication credentials were not provided.'], 401);
        }

        if ($level === self::ADMIN_ONLY && ! $user->isAdmin()) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        if ($level === self::STAFF_OR_ADMIN && ! ($user->isAdmin() || $user->isStaffRole())) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        if ($level === self::READ_PUBLIC && ! ($user->isAdmin() || $user->isStaffRole())) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        return null;
    }

    protected function authorizeAction(Request $request, string $action, ?int $resourceId = null): bool
    {
        return $this->checkAuthorization($request, $action, $resourceId) === null;
    }

    public function index(Request $request): JsonResponse
    {
        if ($error = $this->checkAuthorization($request, 'index')) {
            return $error;
        }

        $query = $this->modelClass::query();
        $user = $request->user();

        if ($this->modelClass === BookingInquiry::class && $user) {
            if (! $user->isAdmin() && ! $user->isStaffRole()) {
                $query->where('customer_id', $user->id);
            } elseif ($user->isStaffRole() && ! $user->isAdmin()) {
                $query->whereHas('hotel.assignedStaff', fn ($q) => $q->whereKey($user->id));
            }
        }

        if ($this->modelClass === ContactMessage::class && $user && $user->isStaffRole() && ! $user->isAdmin()) {
            $query->whereHas('hotel.assignedStaff', fn ($q) => $q->whereKey($user->id));
        }

        if ($this->modelClass === Facility::class && ! $user) {
            $this->applyPublicServiceVisibility($query);
        }

        $this->applyFilters($request, $query);
        foreach ($this->eagerLoads() as $relation) {
            $query->with($relation);
        }
        if ($this->modelClass === BedType::class) {
            $query->orderBy('display_order')->orderBy('name')->orderBy('id');
        } else {
            $query->latest('id');
        }

        $page = $query->paginate((int) $request->query('page_size', 20));

        return response()->json(CompatResponse::page($page));
    }

    public function store(Request $request): JsonResponse
    {
        if ($error = $this->checkAuthorization($request, 'store')) {
            return $error;
        }

        $model = $this->modelClass::query()->create($this->prepareModelInput($request));
        $this->syncManyToMany($model, $request);

        $fresh = $model->fresh($this->eagerLoads());
        AuditService::log('created', AuditService::contentTypeFor($this->modelClass), $fresh);

        return response()->json(CompatResponse::item($fresh), 201);
    }

    protected function modelsWithIcon(): array
    {
        return [
            Facility::class,
            FacilityCategory::class,
        ];
    }

    protected function iconStorageDir(): string
    {
        return match ($this->modelClass) {
            Facility::class => 'facilities',
            FacilityCategory::class => 'facility-categories',
            default => 'icons',
        };
    }

    protected function prepareModelInput(Request $request, ?Model $existing = null): array
    {
        $data = $this->normalizeInput($request->except(['icon']));
        $data = LocalizedMapper::mapInputForSave($this->modelClass, $data, null, $existing !== null && $existing->exists);

        foreach (['is_active', 'is_featured', 'advance_booking_required', 'smoking_allowed', 'extra_bed_allowed', 'breakfast_included'] as $field) {
            if (! $request->has($field)) {
                continue;
            }
            $value = $request->input($field);
            if (is_string($value)) {
                $data[$field] = in_array(strtolower($value), ['true', '1', 'yes'], true);
            }
        }

        if ($this->modelClass === Facility::class) {
            unset($data['hotel_id'], $data['property_ids'], $data['hotel_ids'], $data['properties'], $data['hotels']);
            if (array_key_exists('service_category_id', $data)) {
                $data['facility_category_id'] = $data['service_category_id'];
                unset($data['service_category_id']);
            }
        }

        if ($this->modelClass === RoomType::class) {
            unset($data['facility_ids'], $data['amenity_ids'], $data['prices']);
        }

        if (in_array($this->modelClass, $this->modelsWithIcon(), true)) {
            $data = $this->applyIconUpload($request, $data, $existing);
        }

        return $data;
    }

    protected function applyIconUpload(Request $request, array $data, ?Model $existing = null): array
    {
        if (! $request->hasFile('icon')) {
            return $data;
        }

        $file = $request->file('icon');
        if (! $file->isValid()) {
            return $data;
        }

        $this->deleteStoredIcon($existing?->icon);

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $storedPath = $file->storeAs($this->iconStorageDir(), $filename, 'public');
        $data['icon'] = '/media/'.$storedPath;

        return $data;
    }

    protected function deleteStoredIcon(?string $iconPath): void
    {
        if (! $iconPath || ! str_starts_with($iconPath, '/media/')) {
            return;
        }

        Storage::disk('public')->delete(str_replace('/media/', '', $iconPath));
    }

    protected function eagerLoads(): array
    {
        return match ($this->modelClass) {
            RoomType::class => ['images', 'prices', 'facilities.category'],
            Facility::class => ['images', 'category', 'hotels'],
            BookingInquiry::class => ['hotel', 'roomType', 'guests', 'bookingCurrency'],
            Hotel::class => ['images', 'reviews', 'policy', 'socialMedia', 'contacts', 'setupStatus', 'faqs', 'facilities.category', 'facilities.images'],
            default => [],
        };
    }

    protected function applyPublicServiceVisibility($query): void
    {
        $query
            ->where('is_active', true)
            ->where(function ($inner) {
                $inner
                    ->doesntHave('hotels')
                    ->orWhereHas('hotels', fn ($q) => $q
                        ->where('is_active', true)
                        ->where('publishing_status', 'published'));
            });
    }

    public function show(int $id): JsonResponse
    {
        $request = request();
        if ($error = $this->checkAuthorization($request, 'show', $id)) {
            return $error;
        }

        $query = $this->modelClass::query();
        $user = $request->user();
        if ($this->modelClass === Facility::class && ! $user) {
            $this->applyPublicServiceVisibility($query);
        }

        foreach ($this->eagerLoads() as $relation) {
            $query->with($relation);
        }

        return response()->json(CompatResponse::item($query->findOrFail($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($error = $this->checkAuthorization($request, 'update', $id)) {
            return $error;
        }

        $model = $this->modelClass::query()->findOrFail($id);
        $input = $this->prepareModelInput($request, $model);
        $changes = AuditService::changes($model, $input);
        $model->fill($input)->save();
        $this->syncManyToMany($model, $request);
        if ($model instanceof Facility) {
            $model->hotels()->pluck('hotels.id')->each(fn ($hotelId) => PublicHotelCache::flushHotel((int) $hotelId));
        }

        $fresh = $model->fresh($this->eagerLoads());
        AuditService::log('updated', AuditService::contentTypeFor($this->modelClass), $fresh, $changes);

        return response()->json(CompatResponse::item($fresh));
    }

    public function destroy(int $id): JsonResponse
    {
        $request = request();
        if ($error = $this->checkAuthorization($request, 'destroy', $id)) {
            return $error;
        }

        $model = $this->modelClass::query()->findOrFail($id);
        $contentType = AuditService::contentTypeFor($this->modelClass);

        if (in_array($this->modelClass, $this->modelsWithIcon(), true)) {
            $this->deleteStoredIcon($model->icon);
        }

        if ($model instanceof Facility) {
            $model->hotels()->pluck('hotels.id')->each(fn ($hotelId) => PublicHotelCache::flushHotel((int) $hotelId));
        }

        AuditService::log('deleted', $contentType, $model);
        $model->delete();

        return response()->json(null, 204);
    }

    protected function normalizeInput(array $input): array
    {
        $aliases = [
            'property' => 'hotel_id',
            'room_type' => 'room_type_id',
            'service' => 'facility_id',
            'facility' => 'facility_id',
            'category' => 'facility_category_id',
            'customer' => 'customer_id',
        ];

        foreach ($aliases as $from => $to) {
            if (array_key_exists($from, $input)) {
                $input[$to] = $input[$from];
                unset($input[$from]);
            }
        }

        return $input;
    }

    protected function syncManyToMany(Model $model, Request $request): void
    {
        if ($model instanceof Hotel) {
            if ($request->has('facility_ids')) {
                $model->facilities()->sync($request->input('facility_ids', []));
                PublicHotelCache::flushHotel($model);
            } elseif ($request->has('amenity_ids')) {
                $model->facilities()->sync($request->input('amenity_ids', []));
                PublicHotelCache::flushHotel($model);
            }
        }

        if ($model instanceof RoomType) {
            if ($request->has('facility_ids')) {
                $model->facilities()->sync($request->input('facility_ids', []));
            } elseif ($request->has('amenity_ids')) {
                $model->facilities()->sync($request->input('amenity_ids', []));
            }
        }

        if ($model instanceof Facility) {
            if ($request->has('property_ids') || $request->has('hotel_ids')) {
                $affectedHotelIds = $model->hotels()->pluck('hotels.id')->merge($request->input('property_ids', $request->input('hotel_ids', [])))->unique();
                $model->hotels()->sync($request->input('property_ids', $request->input('hotel_ids', [])));
                $affectedHotelIds->each(fn ($hotelId) => PublicHotelCache::flushHotel((int) $hotelId));

                return;
            }

            $hotelIds = $request->input('properties', $request->input('hotels'));
            if (is_array($hotelIds)) {
                $affectedHotelIds = $model->hotels()->pluck('hotels.id')->merge($hotelIds)->unique();
                $model->hotels()->sync($hotelIds);
                $affectedHotelIds->each(fn ($hotelId) => PublicHotelCache::flushHotel((int) $hotelId));

                return;
            }

            $hotelId = $request->input('property', $request->input('hotel_id'));
            if ($hotelId) {
                $model->hotels()->syncWithoutDetaching([(int) $hotelId]);
                PublicHotelCache::flushHotel((int) $hotelId);
            }
        }
    }

    protected function applyFilters(Request $request, $query): void
    {
        $map = [
            'property' => 'hotel_id',
            'room_type' => 'room_type_id',
            'service' => 'facility_id',
            'facility' => 'facility_id',
            'category' => 'facility_category_id',
            'status' => 'status',
            'role' => 'role',
            'is_active' => 'is_active',
            'is_featured' => 'is_featured',
            'pricing_type' => 'pricing_type',
            'currency' => 'currency',
            'reason' => 'reason',
            'is_published' => 'is_published',
            'slug' => 'slug',
        ];

        foreach ($map as $param => $column) {
            if ($request->query($param) !== null && $request->query($param) !== '') {
                $value = $request->query($param);
                if (in_array($value, ['true', 'false'], true)) {
                    $value = $value === 'true';
                }
                if ($this->modelClass === Facility::class && $param === 'property') {
                    $query->whereHas('hotels', fn ($q) => $q->whereKey($value));

                    continue;
                }
                $query->where($column, $value);
            }
        }

        if ($search = $request->query('search')) {
            $searchColumns = match ($this->modelClass) {
                User::class => ['full_name', 'email', 'phone'],
                default => ['name', 'full_name', 'email', 'customer_name', 'subject'],
            };

            $query->where(function ($inner) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $inner->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }
    }
}
