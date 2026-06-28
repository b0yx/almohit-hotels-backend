<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogMediaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isAdmin() && ! $user->isStaffRole())) {
            abort(403, 'You do not have permission to perform this action.');
        }

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'],
        ]);

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::uuid()->toString().'.'.$extension;
        $storedPath = $file->storeAs('blog', $filename, 'public');

        return response()->json([
            'path' => $storedPath,
            'featured_image' => '/storage/'.$storedPath,
            'url' => url('/storage/'.$storedPath),
        ], 201);
    }
}
