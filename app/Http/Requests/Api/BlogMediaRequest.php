<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

class BlogMediaRequest extends FormRequest
{
    /**
     * MIME-to-extension whitelist.
     *
     * Used both for validation AND for deriving a safe stored extension
     * (never trust getClientOriginalExtension for the saved filename).
     */
    public const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    public const ALLOWED_VIDEO_MIMES = [
        'video/mp4'       => 'mp4',
        'video/webm'      => 'webm',
        'video/quicktime' => 'mov',
    ];

    /** Max sizes in kilobytes. */
    public const IMAGE_MAX_KB = 10_240;  // 10 MB
    public const VIDEO_MAX_KB = 51_200;  // 50 MB

    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isStaffRole());
    }

    /**
     * Resolve the uploaded file: prefer `file`, fall back to `image`.
     */
    public function resolvedUpload(): ?UploadedFile
    {
        return $this->file('file') ?? $this->file('image');
    }

    /**
     * Prepare data before validation runs.
     *
     * Laravel's validator cannot natively express "validate field A, but if
     * absent validate field B instead". We merge the resolved upload into a
     * canonical `_media` key so standard rules can target a single field.
     */
    protected function prepareForValidation(): void
    {
        $resolved = $this->resolvedUpload();

        if ($resolved) {
            $this->merge(['_media' => $resolved]);
        }
    }

    public function rules(): array
    {
        $allMimeTypes = array_merge(
            array_keys(self::ALLOWED_IMAGE_MIMES),
            array_keys(self::ALLOWED_VIDEO_MIMES),
        );

        return [
            '_media' => [
                'required',
                File::types($allMimeTypes)->max(self::VIDEO_MAX_KB),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            '_media.required' => 'A file (image or video) is required.',
            '_media.mimetypes' => 'The file type is not supported.',
            '_media.max' => 'The file is too large.',
        ];
    }

    /**
     * Run additional checks that go beyond simple declarative rules:
     * - per-type size limits (images 10 MB vs videos 50 MB)
     * - reject any MIME not explicitly whitelisted (defence-in-depth)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var UploadedFile|null $upload */
            $upload = $this->resolvedUpload();

            if (! $upload || ! $upload->isValid()) {
                return; // Already caught by 'required' rule.
            }

            $mime = $upload->getMimeType();

            // Explicit whitelist — catches edge cases the `mimetypes` rule may miss.
            if (! $this->isAllowedMime($mime)) {
                $validator->errors()->add(
                    '_media',
                    "Unsupported file type: {$mime}."
                );

                return;
            }

            // Per-type size enforcement.
            if ($this->isImageMime($mime)) {
                $maxBytes = self::IMAGE_MAX_KB * 1024;
                $maxLabel = (self::IMAGE_MAX_KB / 1024).' MB';
            } else {
                $maxBytes = self::VIDEO_MAX_KB * 1024;
                $maxLabel = (self::VIDEO_MAX_KB / 1024).' MB';
            }

            if ($upload->getSize() > $maxBytes) {
                $validator->errors()->add(
                    '_media',
                    "The file must not exceed {$maxLabel}."
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        // Re-key `_media` errors under `file` so the frontend sees a predictable field name.
        $bag = $errors->toArray();
        if (isset($bag['_media'])) {
            $bag['file'] = $bag['_media'];
            unset($bag['_media']);
        }

        $firstMessage = $errors->first();

        $response = response()->json([
            'message' => $firstMessage,
            'errors'  => $bag,
        ], 422);

        throw new HttpResponseException($response);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isImageMime(string $mime): bool
    {
        return isset(self::ALLOWED_IMAGE_MIMES[$mime]);
    }

    public function isAllowedMime(string $mime): bool
    {
        return isset(self::ALLOWED_IMAGE_MIMES[$mime])
            || isset(self::ALLOWED_VIDEO_MIMES[$mime]);
    }

    /**
     * Derive the stored file extension from the validated MIME type.
     *
     * Never uses getClientOriginalExtension() — a user can send
     * `evil.php.jpg` and the client extension would be `jpg` while the
     * MIME is actually `application/x-php`.
     */
    public function safeExtension(): string
    {
        $mime = $this->resolvedUpload()->getMimeType();

        return self::ALLOWED_IMAGE_MIMES[$mime]
            ?? self::ALLOWED_VIDEO_MIMES[$mime]
            ?? 'bin';
    }

    /**
     * Determine the media type category for the response payload.
     */
    public function mediaType(): string
    {
        return $this->isImageMime($this->resolvedUpload()->getMimeType())
            ? 'image'
            : 'video';
    }
}
