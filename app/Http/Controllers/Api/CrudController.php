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
    public function __construct(protected string $modelClass) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->modelClass::query();
        $this->applyFilters($request, $query);
        $page = $query->latest('id')->paginate((int) $request->query('page_size', 20));

        return response()->json(CompatResponse::page($page));
    }

    public function store(Request $request): JsonResponse
    {
        $model = $this->modelClass::query()->create($this->normalizeInput($request->all()));
        $this->syncManyToMany($model, $request);

        return response()->json(CompatResponse::item($model->fresh()), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(CompatResponse::item($this->modelClass::query()->findOrFail($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->modelClass::query()->findOrFail($id);
        $model->fill($this->normalizeInput($request->all()))->save();
        $this->syncManyToMany($model, $request);

        return response()->json(CompatResponse::item($model->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
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
