<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BlogCategoryRequest;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategory;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        $query = BlogCategory::query();
        if ($request->query('locale')) {
            $query->where('locale', $request->query('locale'));
        }

        $categories = $query->orderBy('name')->paginate($this->pageSize($request));

        return response()->json([
            'count' => $categories->total(),
            'next' => $categories->nextPageUrl(),
            'previous' => $categories->previousPageUrl(),
            'results' => BlogCategoryResource::collection($categories->getCollection())->resolve($request),
        ]);
    }

    public function store(BlogCategoryRequest $request): JsonResponse
    {
        $category = BlogCategory::query()->create($request->validated());
        AuditService::log('created', 'blog_category', $category);

        return response()->json(BlogCategoryResource::make($category)->resolve($request), 201);
    }

    public function show(Request $request, BlogCategory $category): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        return response()->json(BlogCategoryResource::make($category)->resolve($request));
    }

    public function update(BlogCategoryRequest $request, BlogCategory $category): JsonResponse
    {
        $data = $request->validated();
        $changes = AuditService::changes($category, $data);
        $category->fill($data)->save();
        AuditService::log('updated', 'blog_category', $category, $changes);

        return response()->json(BlogCategoryResource::make($category)->resolve($request));
    }

    public function destroy(Request $request, BlogCategory $category): JsonResponse
    {
        $this->authorizeBlogAdmin($request);

        if ($category->posts()->exists()) {
            return response()->json(['detail' => 'Cannot delete a category that has blog posts.'], 422);
        }

        AuditService::log('deleted', 'blog_category', $category);
        $category->delete();

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
