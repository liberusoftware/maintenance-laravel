<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\TransitionWorkOrder;
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
