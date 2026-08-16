<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MenuItemResource;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class PublicMenuController extends Controller
{
    /**
     * Public customer-facing menu for a restaurant, resolved by workspace slug.
     * No authentication — this is what a QR-code scan lands on. Returns only
     * active categories and their items (with availability flags).
     */
    public function show(string $slug): JsonResponse
    {
        $workspace = Workspace::query()->where('slug', $slug)->firstOrFail();

        $categories = Category::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $items = MenuItem::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('category_id', $categories->pluck('id'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'workspace' => [
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'emoji' => $workspace->emoji,
                'cuisine' => $workspace->cuisine,
                'currency' => $workspace->currency,
            ],
            'categories' => CategoryResource::collection($categories),
            'items' => MenuItemResource::collection($items),
        ]);
    }
}
