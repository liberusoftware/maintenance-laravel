<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderComment;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
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
