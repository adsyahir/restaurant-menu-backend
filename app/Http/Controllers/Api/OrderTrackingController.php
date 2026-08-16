<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicOrderResource;
use App\Models\Order;

class OrderTrackingController extends Controller
{
    /**
     * Public order lookup by unguessable tracking token — for the customer
     * tracking page. No authentication; exposes only a slim, customer-safe view
     * (never the sequential order_number, which is guessable and per-workspace).
     */
    public function show(string $token): PublicOrderResource
    {
        $order = Order::query()
            ->where('public_token', $token)
            ->with(['items', 'table'])
            ->firstOrFail();

        return new PublicOrderResource($order);
    }
}
