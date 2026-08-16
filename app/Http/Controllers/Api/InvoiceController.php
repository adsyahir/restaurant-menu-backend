<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Workspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    /**
     * Camel-case request keys -> snake_case columns.
     *
     * @var array<string, string>
     */
    private const MAP = [
        'number' => 'number',
        'issuedOn' => 'issued_on',
        'amount' => 'amount',
        'status' => 'status',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = $this->workspace($request)
            ->invoices()
            ->orderByDesc('issued_on')
            ->latest()
            ->get();

        return InvoiceResource::collection($invoices);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->workspace($request)->invoices()->create($this->mapped($request->validated()));

        return (new InvoiceResource($invoice))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $invoice): InvoiceResource
    {
        return new InvoiceResource($this->find($request, $invoice));
    }

    public function update(UpdateInvoiceRequest $request, int $invoice): InvoiceResource
    {
        $model = $this->find($request, $invoice);
        $model->update($this->mapped($request->validated()));

        return new InvoiceResource($model->fresh());
    }

    public function destroy(Request $request, int $invoice): JsonResponse
    {
        $this->find($request, $invoice)->delete();

        return response()->json(null, 204);
    }

    /**
     * Stream the invoice as a downloadable PDF.
     */
    public function pdf(Request $request, int $invoice): Response
    {
        $workspace = $this->workspace($request);
        $model = $this->find($request, $invoice);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $model,
            'workspace' => $workspace,
            'currency' => $workspace->currency,
            'ownerPlan' => $workspace->owner?->plan ?? 'pro',
        ]);

        return $pdf->download("{$model->number}.pdf");
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

    private function find(Request $request, int $id): Invoice
    {
        return $this->workspace($request)->invoices()->whereKey($id)->firstOrFail();
    }

    private function workspace(Request $request): Workspace
    {
        return Workspace::findOrFail($request->user()->current_workspace_id);
    }
}
