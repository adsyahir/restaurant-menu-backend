<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuItemController extends Controller
{
    /**
     * List menu items in the current workspace, optionally filtered by category.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = MenuItem::query()
            ->where('workspace_id', $request->user()->current_workspace_id)
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('available'), fn ($q) => $q->where('is_available', $request->boolean('available')))
            ->orderBy('name')
            ->get();

        return MenuItemResource::collection($items);
    }

    public function store(StoreMenuItemRequest $request): MenuItemResource
    {
        $data = $request->validated();

        $item = MenuItem::create([
            'workspace_id' => $request->user()->current_workspace_id,
            'category_id' => $data['categoryId'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'base_price' => $data['basePrice'],
            'is_available' => $data['isAvailable'] ?? true,
            'dietary_tags' => $data['dietaryTags'] ?? [],
            'variants' => $data['variants'] ?? [],
            'add_ons' => $data['addOns'] ?? [],
            'image_url' => $data['imageUrl'] ?? null,
        ]);

        return new MenuItemResource($item);
    }

    public function show(Request $request, int $menuItem): MenuItemResource
    {
        return new MenuItemResource($this->resolve($request, $menuItem)->load('category'));
    }

    public function update(UpdateMenuItemRequest $request, int $menuItem): MenuItemResource
    {
        $item = $this->resolve($request, $menuItem);
        $data = $request->validated();

        $map = [
            'categoryId' => 'category_id',
            'name' => 'name',
            'description' => 'description',
            'basePrice' => 'base_price',
            'isAvailable' => 'is_available',
            'dietaryTags' => 'dietary_tags',
            'variants' => 'variants',
            'addOns' => 'add_ons',
            'imageUrl' => 'image_url',
        ];

        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $item->{$column} = $data[$input];
            }
        }

        $item->save();

        return new MenuItemResource($item);
    }

    public function destroy(Request $request, int $menuItem): JsonResponse
    {
        $this->resolve($request, $menuItem)->delete();

        return response()->json(null, 204);
    }

    private function resolve(Request $request, int $id): MenuItem
    {
        return MenuItem::query()
            ->where('workspace_id', $request->user()->current_workspace_id)
            ->findOrFail($id);
    }
}
