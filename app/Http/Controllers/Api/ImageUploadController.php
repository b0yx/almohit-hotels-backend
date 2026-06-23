<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelImage;
use App\Models\HotelService;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use App\Models\ServiceImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    private function config(): array
    {
        $segment = request()->segment(2);

        return match ($segment) {
            'property-images' => [
                'model' => HotelImage::class,
                'foreign_key' => 'hotel_id',
                'request_key' => 'property',
                'public_key' => 'property',
                'exists_table' => 'hotels',
                'dir' => 'hotels',
            ],
            'room-type-images' => [
                'model' => RoomTypeImage::class,
                'foreign_key' => 'room_type_id',
                'request_key' => 'room_type',
                'public_key' => 'room_type',
                'exists_table' => 'room_types',
                'dir' => 'room-types',
            ],
            'service-images' => [
                'model' => ServiceImage::class,
                'foreign_key' => 'hotel_service_id',
                'request_key' => 'service',
                'public_key' => 'service',
                'exists_table' => 'hotel_services',
                'dir' => 'services',
            ],
            default => abort(404),
        };
    }

    public function index(Request $request): JsonResponse
    {
        $cfg = $this->config();
        $query = $cfg['model']::query();
        $requestKey = $cfg['request_key'];

        if ($request->query($requestKey)) {
            $query->where($cfg['foreign_key'], $request->query($requestKey));
        }

        $results = $query->latest('id')->get()->map(fn ($img) => $this->format($img, $cfg));

        return response()->json([
            'count' => $results->count(),
            'next' => null,
            'previous' => null,
            'results' => $results->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $cfg = $this->config();
        $user = $request->user();

        if ($user && ! $user->isAdmin()) {
            $parentId = (int) $request->input($cfg['request_key']);
            $hasAccess = match ($cfg['dir']) {
                'hotels' => Hotel::query()->whereKey($parentId)->whereHas('assignedStaff', fn ($q) => $q->whereKey($user->id))->exists(),
                'room-types' => RoomType::query()->whereKey($parentId)->whereHas('hotel.assignedStaff', fn ($q) => $q->whereKey($user->id))->exists(),
                'services' => HotelService::query()->whereKey($parentId)->whereHas('hotel.assignedStaff', fn ($q) => $q->whereKey($user->id))->exists(),
                default => false,
            };
            if (! $hasAccess) {
                return response()->json(['detail' => 'You do not have permission to upload images for this resource.'], 403);
            }
        }

        $request->merge($this->normalizeBooleans($request, ['is_cover', 'is_active']));

        $rules = [
            $cfg['request_key'] => ['required', 'integer', 'exists:' . $cfg['exists_table'] . ',id'],
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_cover' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($request->hasFile('image')) {
            $rules['image'] = ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'];
        } else {
            $rules['image'] = ['nullable', 'string'];
        }

        $data = $request->validate($rules);

        $data[$cfg['foreign_key']] = $data[$cfg['request_key']];
        unset($data[$cfg['request_key']]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $storedPath = $file->storeAs($cfg['dir'], $filename, 'public');
            $data['image'] = '/media/' . $storedPath;
            $data['thumbnail'] = null;
        } elseif (!isset($data['image']) || $data['image'] === null) {
            unset($data['image']);
        }

        $model = $cfg['model']::query()->create($data);

        return response()->json($this->format($model->fresh(), $cfg), 201);
    }

    public function show(string $id): JsonResponse
    {
        $cfg = $this->config();
        $model = $cfg['model']::query()->findOrFail((int) $id);

        return response()->json($this->format($model, $cfg));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $cfg = $this->config();
        $model = $cfg['model']::query()->findOrFail((int) $id);

        $request->merge($this->normalizeBooleans($request, ['is_cover', 'is_active']));

        $rules = [
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_cover' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($request->hasFile('image')) {
            $rules['image'] = ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'];
        }

        $data = $request->validate($rules);

        if ($request->hasFile('image')) {
            if ($model->image && str_starts_with($model->image, '/media/')) {
                Storage::disk('public')->delete(str_replace('/media/', '', $model->image));
            }
            if ($model->thumbnail && str_starts_with($model->thumbnail, '/media/')) {
                Storage::disk('public')->delete(str_replace('/media/', '', $model->thumbnail));
            }

            $file = $request->file('image');
            $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $storedPath = $file->storeAs($cfg['dir'], $filename, 'public');
            $data['image'] = '/media/' . $storedPath;
            $data['thumbnail'] = null;
        }

        $model->fill($data)->save();

        return response()->json($this->format($model->fresh(), $cfg));
    }

    public function destroy(string $id): JsonResponse
    {
        $cfg = $this->config();
        $model = $cfg['model']::query()->findOrFail((int) $id);

        if ($model->image && str_starts_with($model->image, '/media/')) {
            Storage::disk('public')->delete(str_replace('/media/', '', $model->image));
        }
        if ($model->thumbnail && str_starts_with($model->thumbnail, '/media/')) {
            Storage::disk('public')->delete(str_replace('/media/', '', $model->thumbnail));
        }

        $model->delete();

        return response()->json(null, 204);
    }

    private function normalizeBooleans(Request $request, array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                if (is_string($val)) {
                    $result[$field] = in_array(strtolower($val), ['true', '1', 'yes'], true);
                }
            }
        }
        return $result;
    }

    private function format($image, array $cfg): array
    {
        $data = $image->toArray();
        $data[$cfg['public_key']] = $data[$cfg['foreign_key']];
        $data['image_url'] = $data['image'];
        unset($data[$cfg['foreign_key']]);

        return $data;
    }
}
