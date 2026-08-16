<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * List orders in the current workspace, newest first, optionally by status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('workspace_id', $request->user()->current_workspace_id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with(['items', 'table', 'creator'])
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): OrderResource
    {
        $data = $request->validated();
        $workspaceId = $request->user()->current_workspace_id;

        $order = DB::transaction(function () use ($data, $workspaceId, $request) {
            [$lines, $subtotal] = $this->reconcileItems($data['items'], $workspaceId);

            $order = Order::create([
                'workspace_id' => $workspaceId,
                'order_number' => $this->nextOrderNumber($workspaceId),
                'table_id' => $data['tableId'] ?? null,
                'created_by' => $request->user()->id,
                'status' => $data['status'] ?? 'placed',
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
                'payment_method' => $data['paymentMethod'] ?? null,
                'placed_at' => now(),
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        return new OrderResource($order->load(['items', 'table', 'creator']));
    }

    public function show(Request $request, int $order): OrderResource
    {
        return new OrderResource(
            $this->resolve($request, $order)->load(['items', 'table', 'creator'])
        );
    }

    public function update(UpdateOrderRequest $request, int $order): OrderResource
    {
        $model = $this->resolve($request, $order);
        $data = $request->validated();
        $workspaceId = $request->user()->current_workspace_id;

        DB::transaction(function () use ($model, $data, $workspaceId) {
            if (array_key_exists('tableId', $data)) {
                $model->table_id = $data['tableId'];
            }
            if (array_key_exists('status', $data)) {
                $model->status = $data['status'];
            }
            if (array_key_exists('notes', $data)) {
                $model->notes = $data['notes'];
            }
            if (array_key_exists('paymentMethod', $data)) {
                $model->payment_method = $data['paymentMethod'];
            }

            if (array_key_exists('items', $data)) {
                [$lines, $subtotal] = $this->reconcileItems($data['items'], $workspaceId);
                $model->items()->delete();
                $model->items()->createMany($lines);
                $model->subtotal = $subtotal;
                $model->total = $subtotal;
            }

            $model->save();
        });

        return new OrderResource($model->load(['items', 'table', 'creator']));
    }

    public function destroy(Request $request, int $order): JsonResponse
    {
        $this->resolve($request, $order)->delete();

        return response()->json(null, 204);
    }

    /**
     * Normalise client line items against the workspace menu. When a line
     * references a real menu item, its price and name are taken from the
     * database (never trusted from the client); a matching variant's price
     * modifier is added. Ad-hoc lines (no menuItemId) keep the client values.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    private function reconcileItems(array $items, int $workspaceId): array
    {
        $ids = collect($items)->pluck('menuItemId')->filter()->unique()->all();

        $menuItems = MenuItem::query()
            ->where('workspace_id', $workspaceId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $subtotal = 0.0;
        $lines = [];

        foreach ($items as $line) {
            $quantity = (int) $line['quantity'];
            $menuItem = isset($line['menuItemId']) ? $menuItems->get($line['menuItemId']) : null;

            if ($menuItem !== null) {
                $unitPrice = (float) $menuItem->base_price + $this->variantModifier($menuItem, $line['variantLabel'] ?? null);
                $name = $menuItem->name;
            } else {
                $unitPrice = (float) $line['unitPrice'];
                $name = $line['name'];
            }

            $subtotal += $unitPrice * $quantity;

            $lines[] = [
                'menu_item_id' => $menuItem?->id,
                'name' => $name,
                'variant_label' => $line['variantLabel'] ?? null,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'notes' => $line['notes'] ?? null,
            ];
        }

        return [$lines, round($subtotal, 2)];
    }

    /**
     * The price modifier for a variant label on a menu item, or 0 if none match.
     */
    private function variantModifier(MenuItem $menuItem, ?string $variantLabel): float
    {
        if ($variantLabel === null) {
            return 0.0;
        }

        foreach ($menuItem->variants ?? [] as $variant) {
            if (($variant['name'] ?? null) === $variantLabel) {
                return (float) ($variant['priceModifier'] ?? 0);
            }
        }

        return 0.0;
    }

    /**
     * Next sequential order number for a workspace (ORD-0001). Locks existing
     * rows for the workspace so concurrent creates cannot collide, and uses the
     * max existing number so deletions never cause a reused number.
     */
    private function nextOrderNumber(int $workspaceId): string
    {
        $last = Order::where('workspace_id', $workspaceId)
            ->lockForUpdate()
            ->max('order_number');

        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'ORD-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function resolve(Request $request, int $id): Order
    {
        return Order::query()
            ->where('workspace_id', $request->user()->current_workspace_id)
            ->findOrFail($id);
    }
}
