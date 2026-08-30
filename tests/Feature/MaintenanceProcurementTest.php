<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Procurement\Actions\ApprovePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Actions\PlacePurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Actions\ReceivePurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\RejectPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\TransitionPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

it('places purchase orders and records tenant-scoped receipts through the API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('procurement-api-test')->plainTextToken;

    $created = $this->withToken($token)->postJson('/api/v1/maintenance/procurement/purchase-orders', [
        'order_number' => 'PO-1001',
        'supplier_name' => 'Parts Supplier',
        'amount' => 125,
        'items' => [['part_number' => 'P-1', 'quantity' => 2]],
    ])->assertCreated()->json('data.id');

    $this->withToken($token)->postJson("/api/v1/maintenance/procurement/purchase-orders/{$created}/place")
        ->assertOk()->assertJsonPath('data.attributes.status', 'ordered');
    $this->withToken($token)->postJson("/api/v1/maintenance/procurement/purchase-orders/{$created}/receive", [
        'items' => [['part_number' => 'P-1', 'quantity' => 2]],
        'notes' => 'Received in full',
    ])->assertOk()->assertJsonPath('data.attributes.status', 'received');

    $this->withToken($token)->getJson('/api/v1/maintenance/procurement/purchase-orders')
        ->assertOk()->assertJsonPath('data.0.attributes.receipts.0.notes', 'Received in full');
});

it('requires an ordered purchase order before receiving it', function () {
    $team = Team::factory()->create();
    $order = app(CreatePurchaseOrder::class)->handle($team->id, ['order_number' => 'PO-1002', 'amount' => 50]);

    expect(fn () => app(ReceivePurchaseOrder::class)->handle($team->id, $order, ['items' => [['quantity' => 1]]]))
        ->toThrow(ValidationException::class);

    $order = app(PlacePurchaseOrder::class)->handle($team->id, $order);
    $received = app(ReceivePurchaseOrder::class)->handle($team->id, $order, ['items' => [['quantity' => 1]]]);

    expect($received->status)->toBe('received')->and($received->receipts)->toHaveCount(1);
});

it('creates and approves a tenant-scoped purchase request', function () {
    $team = Team::factory()->create();
    $requester = User::factory()->create(['current_team_id' => $team->id]);
    $approver = User::factory()->create(['current_team_id' => $team->id]);
    $request = app(CreatePurchaseRequest::class)->handle($team->id, ['title' => 'Pump seal', 'amount' => 125.50, 'requested_by' => $requester->id]);
    $request = app(ApprovePurchaseRequest::class)->handle($team->id, $request, $approver->id);

    expect($request)->toBeInstanceOf(PurchaseRequest::class)
        ->and($request->team_id)->toBe($team->id)
        ->and($request->status)->toBe('approved')
        ->and($request->approved_by)->toBe($approver->id)
        ->and($request->metadata['status_history'][0]['to'])->toBe('approved');
});

it('prevents self-approval of purchase requests', function () {
    $team = Team::factory()->create();
    $requester = User::factory()->create(['current_team_id' => $team->id]);
    $request = app(CreatePurchaseRequest::class)->handle($team->id, ['title' => 'Pump seal', 'amount' => 10, 'requested_by' => $requester->id]);

    expect(fn () => app(ApprovePurchaseRequest::class)->handle($team->id, $request, $requester->id))
        ->toThrow(ValidationException::class);
});

it('rejects pending purchase requests and records the reason', function () {
    $team = Team::factory()->create();
    $requester = User::factory()->create(['current_team_id' => $team->id]);
    $approver = User::factory()->create(['current_team_id' => $team->id]);
    $request = app(CreatePurchaseRequest::class)->handle($team->id, ['title' => 'Pump seal', 'amount' => 10, 'requested_by' => $requester->id]);

    $rejected = app(RejectPurchaseRequest::class)->handle($team->id, $request, $approver->id, 'Budget unavailable');

    expect($rejected->status)->toBe('rejected')
        ->and($rejected->metadata['rejection_reason'])->toBe('Budget unavailable')
        ->and($rejected->metadata['status_history'][0]['to'])->toBe('rejected');
});

it('provides procurement status query scopes', function () {
    $team = Team::factory()->create();
    $create = app(CreatePurchaseRequest::class);
    $pending = $create->handle($team->id, ['title' => 'Pending order', 'amount' => 100]);
    $approved = $create->handle($team->id, ['title' => 'Approved order', 'amount' => 200]);
    $approver = User::factory()->create(['current_team_id' => $team->id]);
    app(ApprovePurchaseRequest::class)->handle($team->id, $approved, $approver->id);

    expect(PurchaseRequest::query()->where('team_id', $team->id)->pending()->whereKey($pending)->exists())->toBeTrue()
        ->and(PurchaseRequest::query()->where('team_id', $team->id)->approved()->whereKey($approved)->exists())->toBeTrue();
});

it('moves approved purchase requests through ordering and receiving', function () {
    $team = Team::factory()->create();
    $requester = User::factory()->create(['current_team_id' => $team->id]);
    $approver = User::factory()->create(['current_team_id' => $team->id]);
    $request = app(CreatePurchaseRequest::class)->handle($team->id, ['title' => 'Pump seal', 'amount' => 10, 'requested_by' => $requester->id]);
    $request = app(ApprovePurchaseRequest::class)->handle($team->id, $request, $approver->id);
    $transition = app(TransitionPurchaseRequest::class);

    $request = $transition->handle($team->id, $request, 'ordered', $approver->id);
    expect($request->status)->toBe('ordered');
    $request = $transition->handle($team->id, $request, 'received', $approver->id);
    expect($request->status)->toBe('received')
        ->and($request->metadata['status_history'])->toHaveCount(3);
    expect(fn () => $transition->handle($team->id, $request, 'cancelled', $approver->id))
        ->toThrow(ValidationException::class);
});
