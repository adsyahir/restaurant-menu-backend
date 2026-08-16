<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BillingController extends Controller
{
    /**
     * Per-plan limits. 0 = unlimited (mirrors the frontend's "Unlimited on Pro").
     *
     * @var array<string, array{orders: int, staff: int, menuItems: int}>
     */
    private const LIMITS = [
        'free' => ['orders' => 100, 'staff' => 3, 'menuItems' => 30],
        'pro' => ['orders' => 0, 'staff' => 10, 'menuItems' => 0],
        'business' => ['orders' => 0, 'staff' => 0, 'menuItems' => 0],
    ];

    /**
     * Current plan + real usage for the authenticated user's workspace.
     *
     * Payment method and invoices are intentionally omitted — wire Laravel
     * Cashier / Stripe for those. This returns only what the DB actually knows.
     */
    public function show(Request $request): JsonResponse
    {
        $workspace = Workspace::findOrFail($request->user()->current_workspace_id);
        $limits = self::LIMITS[$workspace->plan] ?? self::LIMITS['free'];

        $ordersThisMonth = Order::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', '!=', 'cancelled')
            ->where('placed_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $staffSeats = $workspace->members()->count();
        $menuItems = MenuItem::where('workspace_id', $workspace->id)->count();
        $categories = Category::where('workspace_id', $workspace->id)->count();

        $defaultMethod = $workspace->paymentMethods()->orderByDesc('is_default')->latest()->first();
        $invoices = $workspace->invoices()->orderByDesc('issued_on')->latest()->get();

        return response()->json([
            'plan' => $workspace->plan,
            'subscription' => [
                'status' => $workspace->subscription_status,
                'renewsOn' => $workspace->renews_on?->toDateString(),
            ],
            'usage' => [
                'ordersThisMonth' => $ordersThisMonth,
                'ordersLimit' => $limits['orders'],
                'staffSeats' => $staffSeats,
                'staffLimit' => $limits['staff'],
                'menuItems' => $menuItems,
                'menuItemsLimit' => $limits['menuItems'],
                'categories' => $categories,
            ],
            'paymentMethod' => $defaultMethod ? new PaymentMethodResource($defaultMethod) : null,
            'invoices' => InvoiceResource::collection($invoices),
        ]);
    }
}
