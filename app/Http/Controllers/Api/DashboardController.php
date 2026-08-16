<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Aggregate order stats + top sellers for the current workspace.
     */
    public function index(Request $request): JsonResponse
    {
        $workspaceId = $request->user()->current_workspace_id;
        $range = in_array($request->query('range'), ['today', 'week', 'month'], true)
            ? $request->query('range')
            : 'today';

        [$start, $prevStart, $prevEnd] = $this->window($range);

        $current = $this->summarize($workspaceId, $start, null);
        $previous = $this->summarize($workspaceId, $prevStart, $prevEnd);

        return response()->json([
            'range' => $range,
            'summary' => [
                'totalOrders' => $current['orders'],
                'revenue' => round($current['revenue'], 2),
                'avgOrderValue' => $current['orders'] > 0
                    ? round($current['revenue'] / $current['orders'], 2)
                    : 0,
                'ordersDelta' => $this->delta($current['orders'], $previous['orders']),
                'revenueDelta' => $this->delta($current['revenue'], $previous['revenue']),
            ],
            'topSelling' => $this->topSelling($workspaceId, $start),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon}
     */
    private function window(string $range): array
    {
        $now = Carbon::now();

        return match ($range) {
            'week' => [$now->copy()->startOfWeek(), $now->copy()->startOfWeek()->subWeek(), $now->copy()->startOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->startOfMonth()->subMonth(), $now->copy()->startOfMonth()],
            default => [$now->copy()->startOfDay(), $now->copy()->startOfDay()->subDay(), $now->copy()->startOfDay()],
        };
    }

    /**
     * @return array{orders: int, revenue: float}
     */
    private function summarize(int $workspaceId, Carbon $start, ?Carbon $end): array
    {
        $query = Order::query()
            ->where('workspace_id', $workspaceId)
            ->where('status', '!=', 'cancelled')
            ->where('placed_at', '>=', $start);

        if ($end !== null) {
            $query->where('placed_at', '<', $end);
        }

        return [
            'orders' => (int) $query->count(),
            'revenue' => (float) $query->sum('total'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topSelling(int $workspaceId, Carbon $start): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.workspace_id', $workspaceId)
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.placed_at', '>=', $start)
            ->groupBy('order_items.name')
            ->selectRaw('order_items.name as name')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->selectRaw('SUM(order_items.unit_price * order_items.quantity) as revenue')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'quantitySold' => (int) $row->quantity_sold,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    private function delta(float $current, float $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}
