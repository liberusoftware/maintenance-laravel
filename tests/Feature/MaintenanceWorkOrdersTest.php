<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderComment;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderDependency;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderEvidence;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\DeleteWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\TransitionWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\UpdateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

it('creates and transitions a tenant-scoped work order', function () {
    $team = Team::factory()->create();
    $order = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair pump']);
    $order = app(TransitionWorkOrder::class)->handle($team->id, $order, 'triaged');
    $order = app(TransitionWorkOrder::class)->handle($team->id, $order, 'in_progress');
    $order = app(TransitionWorkOrder::class)->handle($team->id, $order, 'completed');

    expect($order)->toBeInstanceOf(WorkOrder::class)
        ->and($order->number)->toBe('WO-000001')
        ->and($order->status)->toBe('completed')
        ->and($order->completed_at)->not->toBeNull();
});

it('rejects invalid work-order status transitions', function () {
    $team = Team::factory()->create();
    $order = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair pump']);

    expect(fn () => app(TransitionWorkOrder::class)->handle($team->id, $order, 'completed'))
        ->toThrow(ValidationException::class);
});

it('updates work-order details but requires the transition action for status changes', function () {
    $team = Team::factory()->create();
    $order = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair pump']);

    $updated = app(UpdateWorkOrder::class)->handle($team->id, $order, ['title' => 'Repair main pump']);
    expect($updated->title)->toBe('Repair main pump');
    expect(fn () => app(UpdateWorkOrder::class)->handle($team->id, $order, ['status' => 'completed']))
        ->toThrow(ValidationException::class);
});

it('retains legacy assignment and maintenance tracking fields in the modular model', function () {
    $team = Team::factory()->create();
    $order = app(CreateWorkOrder::class)->handle($team->id, [
        'title' => 'Inspect pump',
        'location' => 'Plant A',
        'equipment_id' => 41,
        'customer_id' => 52,
        'assigned_to' => 63,
        'due_date' => now()->addDay(),
        'estimated_minutes' => 90,
        'maintenance_plan_id' => 74,
        'checklist_id' => 85,
    ]);

    expect($order->location)->toBe('Plant A')
        ->and($order->equipment_id)->toBe(41)
        ->and($order->assigned_to)->toBe(63)
        ->and($order->estimated_minutes)->toBe(90)
        ->and($order->maintenance_plan_id)->toBe(74);
});

it('stores comments within the work order tenant boundary', function () {
    $team = Team::factory()->create();
    $order = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair pump']);
    $comment = app(AddWorkOrderComment::class)->handle($team->id, $order, 123, 'Technician dispatched', true);

    expect($comment->work_order_id)->toBe($order->id)
        ->and($comment->team_id)->toBe($team->id)
        ->and($comment->is_internal)->toBeTrue();
    $this->assertDatabaseHas('maintenance_work_order_comments', ['id' => $comment->id, 'comment' => 'Technician dispatched']);
});

it('finds overdue and assigned work orders through domain scopes', function () {
    $team = Team::factory()->create();
    $create = app(CreateWorkOrder::class);
    $overdue = $create->handle($team->id, ['title' => 'Late repair', 'assigned_to' => 12, 'due_date' => now()->subDay()]);
    $upcoming = $create->handle($team->id, ['title' => 'Upcoming repair', 'assigned_to' => 12, 'due_date' => now()->addDays(2)]);
    $completed = $create->handle($team->id, ['title' => 'Finished repair', 'due_date' => now()->subDay()]);
    app(TransitionWorkOrder::class)->handle($team->id, $completed, 'triaged');
    app(TransitionWorkOrder::class)->handle($team->id, $completed, 'in_progress');
    app(TransitionWorkOrder::class)->handle($team->id, $completed, 'completed');

    expect(WorkOrder::query()->where('team_id', $team->id)->overdue()->pluck('id')->all())->toBe([$overdue->id])
        ->and(WorkOrder::query()->where('team_id', $team->id)->dueWithin(7)->pluck('id')->all())->toBe([$upcoming->id])
        ->and(WorkOrder::query()->where('team_id', $team->id)->assignedToUser(12)->count())->toBe(2);
});

it('soft deletes work orders while keeping them recoverable', function () {
    $team = Team::factory()->create();
    $order = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair pump']);

    app(DeleteWorkOrder::class)->handle($team->id, $order);

    expect(WorkOrder::query()->whereKey($order->id)->exists())->toBeFalse()
        ->and(WorkOrder::withTrashed()->whereKey($order->id)->first()->deleted_at)->not->toBeNull();
});

it('provides triaged and blocked work-order scopes', function () {
    $team = Team::factory()->create();
    $triaged = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Triage repair']);
    $blocked = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Blocked repair']);
    $transition = app(TransitionWorkOrder::class);
    $transition->handle($team->id, $triaged, 'triaged');
    $transition->handle($team->id, $blocked, 'triaged');
    $transition->handle($team->id, $blocked, 'in_progress');
    $transition->handle($team->id, $blocked, 'blocked');

    expect(WorkOrder::query()->where('team_id', $team->id)->triaged()->whereKey($triaged)->exists())->toBeTrue()
        ->and(WorkOrder::query()->where('team_id', $team->id)->blocked()->whereKey($blocked)->exists())->toBeTrue();
});

it('supports tenant-scoped dependencies without cycles or self-links', function () {
    $team = Team::factory()->create();
    $first = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Prepare site']);
    $second = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair pump']);
    $third = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Verify repair']);
    $add = app(AddWorkOrderDependency::class);

    $add->handle($team->id, $second, $first);
    $add->handle($team->id, $third, $second);

    expect($third->dependencies()->with('dependsOn')->first()->dependsOn->is($second))->toBeTrue();
    expect(fn () => $add->handle($team->id, $first, $third))->toThrow(ValidationException::class);
    expect(fn () => $add->handle($team->id, $first, $first))->toThrow(ValidationException::class);
});

it('requires prerequisite work orders to be completed first', function () {
    $team = Team::factory()->create();
    $prerequisite = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Prepare site']);
    $dependent = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair pump']);
    app(AddWorkOrderDependency::class)->handle($team->id, $dependent, $prerequisite);
    $transition = app(TransitionWorkOrder::class);
    $transition->handle($team->id, $dependent, 'triaged');
    $transition->handle($team->id, $dependent, 'in_progress');

    expect(fn () => $transition->handle($team->id, $dependent, 'completed'))->toThrow(ValidationException::class);

    $transition->handle($team->id, $prerequisite, 'triaged');
    $transition->handle($team->id, $prerequisite, 'in_progress');
    $transition->handle($team->id, $prerequisite, 'completed');
    expect($transition->handle($team->id, $dependent, 'completed')->status)->toBe('completed');
});

it('stores tenant-scoped work order evidence through the domain action', function () {
    $team = Team::factory()->create();
    $order = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair pump']);
    $evidence = app(AddWorkOrderEvidence::class)->handle($team->id, $order, [
        'kind' => 'photo', 'label' => 'Damaged seal', 'reference' => 'files/repair/damaged-seal.jpg',
        'metadata' => ['captured_at' => '2026-08-29T10:00:00Z'],
    ]);

    expect($evidence->work_order_id)->toBe($order->id)
        ->and($evidence->team_id)->toBe($team->id)
        ->and($order->evidence()->whereKey($evidence)->exists())->toBeTrue();
    expect(fn () => app(AddWorkOrderEvidence::class)->handle($team->id, $order, ['kind' => 'photo']))
        ->toThrow(ValidationException::class);
});
