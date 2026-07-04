<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Support\CompatResponse;
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
            \App\Models\User::class => self::ADMIN_ONLY,
            \App\Models\AuditLog::class => self::ADMIN_ONLY,
            \App\Models\Hotel::class => self::STAFF_OR_ADMIN,
            \App\Models\Facility::class => self::READ_PUBLIC,
            \App\Models\FacilityCategory::class => self::READ_PUBLIC,
            \App\Models\ContactMessage::class => self::STAFF_OR_ADMIN,
            \App\Models\BookingInquiry::class => self::STAFF_OR_ADMIN,
            \App\Models\Review::class => self::STAFF_OR_ADMIN,
        ];
    }

    protected function authorizeAction(Request $request, string $action, ?int $resourceId = null): bool
    {
        $user = $request->user();
        $level = $this->accessMap[$this->modelClass] ?? self::STAFF_OR_ADMIN;

        if ($level === self::ADMIN_ONLY) {
            return $user && $user->isAdmin();
        }

        if ($level === self::STAFF_OR_ADMIN) {
            return $user && ($user->isAdmin() || $user->isStaffRole());
        }

        if ($level === self::READ_PUBLIC) {
            if (in_array($action, ['index', 'show'], true)) {
                return true;
            }
            return $user && ($user->isAdmin() || $user->isStaffRole());
        }

        return true;
    }

    public function index(Request $request): JsonResponse
    {
        if (! $this->authorizeAction($request, 'index')) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $query = $this->modelClass::query();
        $user = $request->user();

        if ($this->modelClass === \App\Models\BookingInquiry::class && $user) {
            if (! $user->isAdmin() && ! $user->isStaffRole()) {
                $query->where('customer_id', $user->id);
            } elseif ($user->isStaffRole() && ! $user->isAdmin()) {
                $query->whereHas('hotel.assignedStaff', fn ($q) => $q->whereKey($user->id));
            }
        }

        if ($this->modelClass === \App\Models\ContactMessage::class && $user && $user->isStaffRole() && ! $user->isAdmin()) {
            $query->whereHas('hotel.assignedStaff', fn ($q) => $q->whereKey($user->id));
        }

        $this->applyFilters($request, $query);
        foreach ($this->eagerLoads() as $relation) {
            $query->with($relation);
        }
        $page = $query->latest('id')->paginate((int) $request->query('page_size', 20));

        return response()->json(CompatResponse::page($page));
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->authorizeAction($request, 'store')) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $model = $this->modelClass::query()->create($this->prepareModelInput($request));
        $this->syncManyToMany($model, $request);

        $fresh = $model->fresh();
        AuditService::log('created', AuditService::contentTypeFor($this->modelClass), $fresh);

        return response()->json(CompatResponse::item($fresh), 201);
    }

    protected function modelsWithIcon(): array
    {
        return [
            \App\Models\Facility::class,
            \App\Models\FacilityCategory::class,
        ];
    }

    protected function iconStorageDir(): string
    {
        return match ($this->modelClass) {
            \App\Models\Facility::class => 'facilities',
            \App\Models\FacilityCategory::class => 'facility-categories',
            default => 'icons',
        };
    }

    protected function prepareModelInput(Request $request, ?Model $existing = null): array
    {
        $data = $this->normalizeInput($request->except(['icon']));

        foreach (['is_active', 'is_featured', 'advance_booking_required', 'smoking_allowed', 'extra_bed_allowed', 'breakfast_included'] as $field) {
            if (! $request->has($field)) {
                continue;
            }
            $value = $request->input($field);
            if (is_string($value)) {
                $data[$field] = in_array(strtolower($value), ['true', '1', 'yes'], true);
            }
        }

        if ($this->modelClass === \App\Models\Facility::class) {
            unset($data['hotel_id']);
            if (array_key_exists('service_category_id', $data)) {
                $data['facility_category_id'] = $data['service_category_id'];
                unset($data['service_category_id']);
            }
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

        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs($this->iconStorageDir(), $filename, 'public');
        $data['icon'] = '/media/' . $storedPath;

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
            \App\Models\RoomType::class => ['images', 'prices'],
            \App\Models\Facility::class => ['images', 'category'],
            \App\Models\BookingInquiry::class => ['hotel', 'roomType', 'guests'],
            \App\Models\Hotel::class => ['amenities', 'images', 'reviews', 'policy', 'socialMedia', 'contacts', 'setupStatus'],
            default => [],
        };
    }

    public function show(int $id): JsonResponse
    {
        $request = request();
        if (! $this->authorizeAction($request, 'show', $id)) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $query = $this->modelClass::query();
        foreach ($this->eagerLoads() as $relation) {
            $query->with($relation);
        }

        return response()->json(CompatResponse::item($query->findOrFail($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $this->authorizeAction($request, 'update', $id)) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $model = $this->modelClass::query()->findOrFail($id);
        $input = $this->prepareModelInput($request, $model);
        $changes = AuditService::changes($model, $input);
        $model->fill($input)->save();
        $this->syncManyToMany($model, $request);

        $fresh = $model->fresh();
        AuditService::log('updated', AuditService::contentTypeFor($this->modelClass), $fresh, $changes);

        return response()->json(CompatResponse::item($fresh));
    }

    public function destroy(int $id): JsonResponse
    {
        $request = request();
        if (! $this->authorizeAction($request, 'destroy', $id)) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $model = $this->modelClass::query()->findOrFail($id);
        $contentType = AuditService::contentTypeFor($this->modelClass);

        if (in_array($this->modelClass, $this->modelsWithIcon(), true)) {
            $this->deleteStoredIcon($model->icon);
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
        if ($model instanceof \App\Models\Hotel) {
            if ($request->has('facility_ids')) {
                $model->facilities()->sync($request->input('facility_ids', []));
            } elseif ($request->has('amenity_ids')) {
                $model->facilities()->sync($request->input('amenity_ids', []));
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
                $query->where($column, $value);
            }
        }

        if ($search = $request->query('search')) {
            $searchColumns = match ($this->modelClass) {
                \App\Models\User::class => ['full_name', 'email', 'phone'],
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
