<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BlogPostRequest;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Services\BlogPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    public function publicIndex(Request $request): JsonResponse
    {
        $query = BlogPost::query()
            ->publiclyVisible()
            ->with(['category', 'author', 'hotel.images', 'faqs']);

        if ($request->query('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->query('hotel_id') !== null && $request->query('hotel_id') !== '') {
            $query->where('hotel_id', $request->query('hotel_id'));
        }

        $this->applySorting($query, $request, 'published_at');

        $posts = $query->paginate($this->pageSize($request));

        return response()->json([
            'count' => $posts->total(),
            'next' => $posts->nextPageUrl(),
            'previous' => $posts->previousPageUrl(),
            'results' => BlogPostResource::collection($posts->getCollection())->resolve($request),
        ]);
    }

    public function publicShow(Request $request, string $slug): JsonResponse
    {
        $post = BlogPost::query()
            ->publiclyVisible()
            ->with(['category', 'author', 'hotel.images', 'hotel.reviews', 'hotel.policy', 'hotel.socialMedia', 'hotel.contacts', 'hotel.setupStatus', 'faqs'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(BlogPostResource::make($post)->resolve($request));
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        $query = BlogPost::query()->with(['category', 'author', 'hotel.images', 'faqs']);

        foreach (['status', 'locale', 'category_id', 'hotel_id', 'author_id'] as $filter) {
            if ($request->query($filter) !== null && $request->query($filter) !== '') {
                $query->where($filter, $request->query($filter));
            }
        }

        if ($request->query('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $this->applySorting($query, $request, 'id');

        $posts = $query->paginate($this->pageSize($request));

        return response()->json([
            'count' => $posts->total(),
            'next' => $posts->nextPageUrl(),
            'previous' => $posts->previousPageUrl(),
            'results' => BlogPostResource::collection($posts->getCollection())->resolve($request),
        ]);
    }

    public function generateSlug(Request $request): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        $request->validate([
            'text' => ['required', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        $text = $request->input('text');
        $slug = Str::slug($text);

        return response()->json([
            'slug' => $slug,
        ]);
    }

    public function store(BlogPostRequest $request, BlogPostService $service): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        $post = $service->create($request->validated());

        return response()->json(
            BlogPostResource::make($post->load(['category', 'author', 'hotel.images', 'faqs']))->resolve($request),
            201
        );
    }

    public function show(Request $request, BlogPost $post): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        return response()->json(
            BlogPostResource::make($post->load(['category', 'author', 'hotel.images', 'faqs']))->resolve($request)
        );
    }

    public function update(BlogPostRequest $request, BlogPost $post, BlogPostService $service): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        $post = $service->update($post, $request->validated());

        return response()->json(
            BlogPostResource::make($post->load(['category', 'author', 'hotel.images', 'faqs']))->resolve($request)
        );
    }

    public function destroy(Request $request, BlogPost $post, BlogPostService $service): JsonResponse
    {
        $this->authorizeBlogAdmin($request);
        $service->delete($post);

        return response()->json(null, 204);
    }

    private function applySorting($query, Request $request, string $defaultSort = 'id'): void
    {
        if ($request->query('sort')) {
            $sortParam = $request->query('sort');
            $direction = str_starts_with($sortParam, '-') ? 'desc' : 'asc';
            $column = ltrim($sortParam, '-');

            $allowed = ['created_at', 'published_at', 'title', 'status', 'id', 'updated_at'];
            if (in_array($column, $allowed, true)) {
                $query->orderBy($column, $direction);

                return;
            }
        }

        if ($defaultSort === 'published_at') {
            $query->latest('published_at');
        } else {
            $query->latest('id');
        }
    }

    private function pageSize(Request $request): int
    {
        $perPage = $request->query('per_page', $request->query('page_size', 20));

        return max(1, min(100, (int) $perPage));
    }

    private function authorizeBlogAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Authentication credentials were not provided.');
        }

        if (! $user->isAdmin() && ! $user->isStaffRole()) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
