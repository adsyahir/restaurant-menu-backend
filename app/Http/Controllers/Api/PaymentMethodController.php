<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    /**
     * Camel-case request keys -> snake_case columns.
     *
     * @var array<string, string>
     */
    private const MAP = [
        'brand' => 'brand',
        'last4' => 'last4',
        'expMonth' => 'exp_month',
        'expYear' => 'exp_year',
        'isDefault' => 'is_default',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $methods = $this->workspace($request)
            ->paymentMethods()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return PaymentMethodResource::collection($methods);
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        $workspace = $this->workspace($request);
        $attributes = $this->mapped($request->validated());

        // First card is always the default; respect an explicit default too.
        $makeDefault = ($attributes['is_default'] ?? false) || $workspace->paymentMethods()->count() === 0;

        $method = DB::transaction(function () use ($workspace, $attributes, $makeDefault) {
            if ($makeDefault) {
                $workspace->paymentMethods()->update(['is_default' => false]);
            }

            $attributes['is_default'] = $makeDefault;

            return $workspace->paymentMethods()->create($attributes);
        });

        return (new PaymentMethodResource($method))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $paymentMethod): PaymentMethodResource
    {
        return new PaymentMethodResource($this->find($request, $paymentMethod));
    }

    public function update(UpdatePaymentMethodRequest $request, int $paymentMethod): PaymentMethodResource
    {
        $workspace = $this->workspace($request);
        $method = $this->find($request, $paymentMethod);
        $attributes = $this->mapped($request->validated());

        DB::transaction(function () use ($workspace, $method, $attributes) {
            // Promoting this card to default demotes the others.
            if (($attributes['is_default'] ?? false) === true) {
                $workspace->paymentMethods()->whereKeyNot($method->id)->update(['is_default' => false]);
            }

            $method->update($attributes);
        });

        return new PaymentMethodResource($method->fresh());
    }

    public function destroy(Request $request, int $paymentMethod): JsonResponse
    {
        $workspace = $this->workspace($request);
        $method = $this->find($request, $paymentMethod);
        $wasDefault = $method->is_default;

        DB::transaction(function () use ($workspace, $method, $wasDefault) {
            $method->delete();

            // Keep exactly one default: promote the most recent remaining card.
            if ($wasDefault) {
                $next = $workspace->paymentMethods()->latest()->first();
                $next?->update(['is_default' => true]);
            }
        });

        return response()->json(null, 204);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapped(array $validated): array
    {
        $attributes = [];

        foreach (self::MAP as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $attributes[$column] = $validated[$input];
            }
        }

        return $attributes;
    }

    private function find(Request $request, int $id): PaymentMethod
    {
        return $this->workspace($request)->paymentMethods()->whereKey($id)->firstOrFail();
    }

    private function workspace(Request $request): Workspace
    {
        return Workspace::findOrFail($request->user()->current_workspace_id);
    }
}
