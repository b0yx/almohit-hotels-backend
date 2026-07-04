<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BlogMediaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BlogMediaController extends Controller
{
    /**
     * Upload a blog media file (image or video).
     *
     * Authorization and validation are handled by BlogMediaRequest.
     * The request resolves `file` (preferred) or `image` fields,
     * validates MIME types and per-type size limits, and derives a
     * safe extension from server-side MIME detection.
     */
    public function store(BlogMediaRequest $request): JsonResponse
    {
        $upload   = $request->resolvedUpload();
        $filename = Str::uuid()->toString().'.'.$request->safeExtension();

        $storedPath = $upload->storeAs('blog', $filename, 'public');

        return response()->json([
            'path'           => $storedPath,
            'featured_image' => '/storage/'.$storedPath,
            'url'            => url('/storage/'.$storedPath),
            'type'           => $request->mediaType(),
        ], 201);
    }
}
