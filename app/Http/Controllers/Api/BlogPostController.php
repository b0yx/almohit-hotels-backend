<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BlogPostRequest;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Services\BlogPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogPostController extends Controller
{
    public function publicIndex(Request $request): JsonResponse
    {
        $posts = BlogPost::query()
            ->publiclyVisible()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate($this->pageSize($request));

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
            ->with(['category', 'author', 'hotel.images', 'hotel.amenities', 'hotel.reviews', 'hotel.policy', 'hotel.socialMedia', 'hotel.contacts', 'hotel.setupStatus'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(BlogPostResource::make($post)->resolve($request));
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        $query = BlogPost::query()->with(['category', 'author', 'hotel.images']);

        foreach (['status', 'locale', 'category_id', 'hotel_id', 'author_id'] as $filter) {
            if ($request->query($filter) !== null && $request->query($filter) !== '') {
                $query->where($filter, $request->query($filter));
            }
        }

        $posts = $query->latest('id')->paginate($this->pageSize($request));

        return response()->json([
            'count' => $posts->total(),
            'next' => $posts->nextPageUrl(),
            'previous' => $posts->previousPageUrl(),
            'results' => BlogPostResource::collection($posts->getCollection())->resolve($request),
        ]);
    }

    public function store(BlogPostRequest $request, BlogPostService $service): JsonResponse
    {
        $post = $service->create($request->validated());

        return response()->json(
            BlogPostResource::make($post->load(['category', 'author', 'hotel.images']))->resolve($request),
            201
        );
    }

    public function show(Request $request, BlogPost $post): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        return response()->json(
            BlogPostResource::make($post->load(['category', 'author', 'hotel.images']))->resolve($request)
        );
    }

    public function update(BlogPostRequest $request, BlogPost $post, BlogPostService $service): JsonResponse
    {
        $post = $service->update($post, $request->validated());

        return response()->json(
            BlogPostResource::make($post->load(['category', 'author', 'hotel.images']))->resolve($request)
        );
    }

    public function destroy(Request $request, BlogPost $post, BlogPostService $service): JsonResponse
    {
        $this->authorizeBlogAdmin($request);
        $service->delete($post);

        return response()->json(null, 204);
    }

    private function pageSize(Request $request): int
    {
        return max(1, min(100, (int) $request->query('page_size', 20)));
    }

    private function authorizeBlogAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->isAdmin() && ! $user->isStaffRole())) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
