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
use Illuminate\Validation\ValidationException;

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

        $this->assertImageUploadSucceeded($request);

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
        $data = $this->normalizeTextFields($data, ['caption', 'alt_text']);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $storedPath = $file->storeAs($cfg['dir'], $filename, 'public');
            $data['image'] = '/media/' . $storedPath;
            $data['thumbnail'] = null;
        } elseif (!isset($data['image']) || $data['image'] === null) {
            unset($data['image']);
        }

        if (! empty($data['is_cover'])) {
            $this->clearCoverFlags($cfg, (int) $data[$cfg['foreign_key']]);
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
        $data = $this->normalizeTextFields($data, ['caption', 'alt_text']);

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

        if (! empty($data['is_cover'])) {
            $this->clearCoverFlags($cfg, (int) $model->{$cfg['foreign_key']}, (int) $model->getKey());
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

    private function assertImageUploadSucceeded(Request $request): void
    {
        if ($request->hasFile('image')) {
            return;
        }

        if (! $request->has('image') && empty($_FILES['image'])) {
            return;
        }

        $error = (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);
        $maxSize = ini_get('upload_max_filesize') ?: '2M';

        $message = match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Image file is too large. Maximum upload size is {$maxSize}.",
            UPLOAD_ERR_PARTIAL => 'Image upload was incomplete. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Server cannot store uploaded files. Ensure storage/app/tmp is writable and PHP upload_tmp_dir is configured.',
            UPLOAD_ERR_NO_FILE => 'No image file was received. Please choose a JPG, PNG, or WEBP file and try again.',
            UPLOAD_ERR_EXTENSION => 'Image upload blocked by server configuration.',
            default => 'Image upload failed. Please try again.',
        };

        throw ValidationException::withMessages(['image' => $message]);
    }

    private function normalizeTextFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = '';
            }
        }

        return $data;
    }

    private function clearCoverFlags(array $cfg, int $parentId, ?int $exceptId = null): void
    {
        $query = $cfg['model']::query()->where($cfg['foreign_key'], $parentId);
        if ($exceptId) {
            $query->whereKeyNot($exceptId);
        }
        $query->update(['is_cover' => false]);
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
