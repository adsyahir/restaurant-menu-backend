<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * List all categories in the current workspace.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where('workspace_id', $request->user()->current_workspace_id)
            ->withCount('menuItems')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $data = $request->validated();

        $category = Category::create([
            'workspace_id' => $request->user()->current_workspace_id,
            'name' => $data['name'],
            'display_order' => $data['displayOrder'] ?? 0,
            'is_active' => $data['isActive'] ?? true,
        ]);

        return new CategoryResource($category);
    }

    public function show(Request $request, int $category): CategoryResource
    {
        $model = $this->resolve($request, $category);

        return new CategoryResource($model->loadCount('menuItems'));
    }

    public function update(UpdateCategoryRequest $request, int $category): CategoryResource
    {
        $model = $this->resolve($request, $category);
        $data = $request->validated();

        $model->fill(array_filter([
            'name' => $data['name'] ?? null,
            'display_order' => $data['displayOrder'] ?? null,
        ], fn ($v) => $v !== null));

        if (array_key_exists('isActive', $data)) {
            $model->is_active = $data['isActive'];
        }

        $model->save();

        return new CategoryResource($model);
    }

    public function destroy(Request $request, int $category): JsonResponse
    {
        $this->resolve($request, $category)->delete();

        return response()->json(null, 204);
    }

    /**
     * Fetch a category, scoped to the current workspace (404 otherwise).
     */
    private function resolve(Request $request, int $id): Category
    {
        return Category::query()
            ->where('workspace_id', $request->user()->current_workspace_id)
            ->findOrFail($id);
    }
}
