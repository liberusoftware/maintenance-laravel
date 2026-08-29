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
