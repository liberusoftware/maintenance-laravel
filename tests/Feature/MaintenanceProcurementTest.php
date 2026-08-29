<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Procurement\Actions\ApprovePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\RejectPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

it('creates and approves a tenant-scoped purchase request', function () {
    $team = Team::factory()->create();
    $requester = User::factory()->create(['current_team_id' => $team->id]);
    $approver = User::factory()->create(['current_team_id' => $team->id]);
    $request = app(CreatePurchaseRequest::class)->handle($team->id, ['title' => 'Pump seal', 'amount' => 125.50, 'requested_by' => $requester->id]);
    $request = app(ApprovePurchaseRequest::class)->handle($team->id, $request, $approver->id);

    expect($request)->toBeInstanceOf(PurchaseRequest::class)
        ->and($request->team_id)->toBe($team->id)
        ->and($request->status)->toBe('approved')
        ->and($request->approved_by)->toBe($approver->id);
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
        ->and($rejected->metadata['rejection_reason'])->toBe('Budget unavailable');
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
