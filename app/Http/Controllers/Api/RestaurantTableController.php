<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantTableRequest;
use App\Http\Requests\UpdateRestaurantTableRequest;
use App\Http\Resources\RestaurantTableResource;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RestaurantTableController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tables = RestaurantTable::query()
            ->where('workspace_id', $request->user()->current_workspace_id)
            ->orderBy('label')
            ->get();

        return RestaurantTableResource::collection($tables);
    }

    public function store(StoreRestaurantTableRequest $request): RestaurantTableResource
    {
        $data = $request->validated();

        $table = RestaurantTable::create([
            'workspace_id' => $request->user()->current_workspace_id,
            'label' => $data['label'],
            'seating_capacity' => $data['seatingCapacity'] ?? 2,
            'status' => $data['status'] ?? 'available',
        ]);

        return new RestaurantTableResource($table);
    }

    public function show(Request $request, int $restaurantTable): RestaurantTableResource
    {
        return new RestaurantTableResource($this->resolve($request, $restaurantTable));
    }

    public function update(UpdateRestaurantTableRequest $request, int $restaurantTable): RestaurantTableResource
    {
        $table = $this->resolve($request, $restaurantTable);
        $data = $request->validated();

        $map = [
            'label' => 'label',
            'seatingCapacity' => 'seating_capacity',
            'status' => 'status',
        ];

        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $table->{$column} = $data[$input];
            }
        }

        $table->save();

        return new RestaurantTableResource($table);
    }

    public function destroy(Request $request, int $restaurantTable): JsonResponse
    {
        $this->resolve($request, $restaurantTable)->delete();

        return response()->json(null, 204);
    }

    private function resolve(Request $request, int $id): RestaurantTable
    {
        return RestaurantTable::query()
            ->where('workspace_id', $request->user()->current_workspace_id)
            ->findOrFail($id);
    }
}
