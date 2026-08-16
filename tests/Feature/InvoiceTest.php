<?php

use App\Models\Invoice;
use App\Models\Workspace;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

it('lists only the current workspace invoices', function () {
    [, $workspace] = actingAsOwner();
    Invoice::factory()->for($workspace)->create(['number' => 'INV-A']);
    Invoice::factory()->create(['number' => 'INV-B']); // other workspace

    getJson('/api/invoices')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.number', 'INV-A');
});

it('creates an invoice', function () {
    [, $workspace] = actingAsOwner();

    postJson('/api/invoices', [
        'number' => 'INV-2026-010', 'issuedOn' => '2026-08-01', 'amount' => 149, 'status' => 'paid',
    ])->assertCreated()
        ->assertJsonPath('data.number', 'INV-2026-010')
        ->assertJsonPath('data.amount', 149);

    $this->assertDatabaseHas('invoices', ['workspace_id' => $workspace->id, 'number' => 'INV-2026-010']);
});

it('rejects a duplicate invoice number in the same workspace', function () {
    [, $workspace] = actingAsOwner();
    Invoice::factory()->for($workspace)->create(['number' => 'INV-DUP']);

    postJson('/api/invoices', [
        'number' => 'INV-DUP', 'issuedOn' => '2026-08-01', 'amount' => 10, 'status' => 'due',
    ])->assertStatus(422)->assertJsonValidationErrors('number');
});

it('updates an invoice status', function () {
    [, $workspace] = actingAsOwner();
    $invoice = Invoice::factory()->for($workspace)->create(['status' => 'due']);

    patchJson("/api/invoices/{$invoice->id}", ['status' => 'paid'])
        ->assertOk()
        ->assertJsonPath('data.status', 'paid');
});

it('deletes an invoice', function () {
    [, $workspace] = actingAsOwner();
    $invoice = Invoice::factory()->for($workspace)->create();

    deleteJson("/api/invoices/{$invoice->id}")->assertNoContent();
    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
});

it('forbids non-admin members from creating invoices', function () {
    actingAsMember('kitchen');

    postJson('/api/invoices', [
        'number' => 'INV-X', 'issuedOn' => '2026-08-01', 'amount' => 10, 'status' => 'due',
    ])->assertForbidden();
});

it('cannot touch another workspace invoice', function () {
    actingAsOwner();
    $foreign = Invoice::factory()->for(Workspace::factory())->create();

    patchJson("/api/invoices/{$foreign->id}", ['status' => 'paid'])->assertNotFound();
});

it('downloads an invoice as a pdf', function () {
    [, $workspace] = actingAsOwner();
    $invoice = Invoice::factory()->for($workspace)->create(['number' => 'INV-PDF']);

    $response = test()->get("/api/invoices/{$invoice->id}/pdf");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('cannot download another workspace invoice pdf', function () {
    actingAsOwner();
    $foreign = Invoice::factory()->for(Workspace::factory())->create();

    test()->get("/api/invoices/{$foreign->id}/pdf")->assertNotFound();
});
