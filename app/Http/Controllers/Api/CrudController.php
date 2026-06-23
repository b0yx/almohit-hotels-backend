<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CompatResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            \App\Models\HotelAmenity::class => self::READ_PUBLIC,
            \App\Models\ServiceCategory::class => self::READ_PUBLIC,
            \App\Models\ContactMessage::class => self::STAFF_OR_ADMIN,
            \App\Models\ChannelManagerConnection::class => self::STAFF_OR_ADMIN,
            \App\Models\BookingInquiry::class => self::STAFF_OR_ADMIN,
            \App\Models\Review::class => self::STAFF_OR_ADMIN,
        ];
    }

    private function authorizeAction(Request $request, string $action, ?int $resourceId = null): bool
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

        if ($this->modelClass === \App\Models\ChannelManagerConnection::class && $user && $user->isStaffRole() && ! $user->isAdmin()) {
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

        $model = $this->modelClass::query()->create($this->normalizeInput($request->all()));
        $this->syncManyToMany($model, $request);

        return response()->json(CompatResponse::item($model->fresh()), 201);
    }

    private function eagerLoads(): array
    {
        return match ($this->modelClass) {
            \App\Models\RoomType::class => ['images', 'prices'],
            \App\Models\HotelService::class => ['images', 'hotel', 'category'],
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
        $model->fill($this->normalizeInput($request->all()))->save();
        $this->syncManyToMany($model, $request);

        return response()->json(CompatResponse::item($model->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        $request = request();
        if (! $this->authorizeAction($request, 'destroy', $id)) {
            return response()->json(['detail' => 'You do not have permission to perform this action.'], 403);
        }

        $this->modelClass::query()->findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    protected function normalizeInput(array $input): array
    {
        $aliases = [
            'property' => 'hotel_id',
            'room_type' => 'room_type_id',
            'service' => 'hotel_service_id',
            'category' => 'service_category_id',
            'customer' => 'customer_id',
        ];

        foreach ($aliases as $from => $to) {
            if (array_key_exists($from, $input)) {
                $input[$to] = $input[$from];
                unset($input[$from]);
            }
        }

        unset($input['amenity_ids'], $input['amenities'], $input['guests']);

        return $input;
    }

    protected function syncManyToMany(Model $model, Request $request): void
    {
        if ($model instanceof \App\Models\Hotel && $request->has('amenity_ids')) {
            $model->amenities()->sync($request->input('amenity_ids', []));
        }
    }

    protected function applyFilters(Request $request, $query): void
    {
        $map = [
            'property' => 'hotel_id',
            'room_type' => 'room_type_id',
            'service' => 'hotel_service_id',
            'category' => 'service_category_id',
            'status' => 'status',
            'is_active' => 'is_active',
            'is_featured' => 'is_featured',
            'pricing_type' => 'pricing_type',
            'currency' => 'currency',
            'reason' => 'reason',
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
            $query->where(function ($inner) use ($search) {
                foreach (['name', 'full_name', 'email', 'customer_name', 'subject'] as $column) {
                    $inner->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }
    }

    protected function ensureSlug(array $data): array
    {
        if (! isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
