<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Commercial\Actions\CreateCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Actions\CreateCommercialLine;
use Liberu\Modules\Maintenance\Commercial\Actions\TransitionCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialLine;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;
use Liberu\Modules\Maintenance\Documents\Actions\CreateMaintenanceDocument;
use Liberu\Modules\Maintenance\Documents\Actions\CreateDocumentVersion;
use Liberu\Modules\Maintenance\Documents\Models\MaintenanceDocument;
use Liberu\Modules\Maintenance\Notes\Models\MaintenanceNote;
use Liberu\Modules\Maintenance\Notes\Actions\CreateMaintenanceNote;
use Liberu\Modules\Maintenance\Tasks\Actions\CreateMaintenanceTask;
use Liberu\Modules\Maintenance\Tasks\Actions\CompleteMaintenanceTask;
use Liberu\Modules\Maintenance\Tasks\Models\MaintenanceTask;
use Liberu\Modules\Maintenance\Portal\Actions\CreatePortalRecord;
use Liberu\Modules\Maintenance\Portal\Actions\TransitionPortalRecord;
use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorContract;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Actions\TransitionVendorContract;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdateVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;
use Liberu\Modules\Maintenance\Procurement\Models\VendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Report\Actions\CreateReportRecord;
use Liberu\Modules\Maintenance\Report\Actions\PublishReport;
use Liberu\Modules\Maintenance\Report\Actions\UpdateReportRecord;
use Liberu\Modules\Maintenance\Report\Models\ReportKind;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;
use Liberu\Modules\Maintenance\Report\Queries\BuildReportSummary;
use Liberu\Modules\Maintenance\Report\Queries\MaintenanceMetrics;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\TransitionWorkOrder;

it('creates tenant-scoped records for the remaining maintenance capabilities', function () {
    $team = Team::factory()->create();

    $commercial = app(CreateCommercialRecord::class)->handle($team->id, ['kind' => 'quote', 'title' => 'Annual contract', 'amount' => 1200]);
    $compliance = app(CreateComplianceRecord::class)->handle($team->id, ['kind' => 'permit', 'title' => 'Boiler permit']);
    $portal = app(CreatePortalRecord::class)->handle($team->id, ['kind' => 'request', 'title' => 'Request a visit']);
    $report = app(CreateReportRecord::class)->handle($team->id, ['kind' => 'backlog', 'title' => 'Open work orders', 'metric_value' => 4]);

    expect($commercial)->toBeInstanceOf(CommercialRecord::class)
        ->and($compliance)->toBeInstanceOf(ComplianceRecord::class)
        ->and($portal)->toBeInstanceOf(PortalRecord::class)
        ->and($report)->toBeInstanceOf(ReportRecord::class)
        ->and($commercial->team_id)->toBe($team->id)
        ->and($report->metric_value)->toBe('4.00');
});

it('builds tenant-scoped operational maintenance metrics from modular work orders', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $create = app(CreateWorkOrder::class);
    $transition = app(TransitionWorkOrder::class);
    $order = $create->handle($team->id, ['title' => 'Repair pump', 'priority' => 'high']);
    $order->forceFill(['submitted_at' => now()->subHours(5)])->save();
    $transition->handle($team->id, $order, 'triaged');
    $transition->handle($team->id, $order, 'in_progress');
    $order->forceFill(['started_at' => now()->subHours(3)])->save();
    $transition->handle($team->id, $order, 'completed');
    $order->forceFill(['completed_at' => now()])->save();
    $create->handle($otherTeam->id, ['title' => 'Other tenant repair']);

    $metrics = app(MaintenanceMetrics::class)->handle($team->id, now()->subDay(), now()->addMinute());

    expect($metrics['total_work_orders'])->toBe(1)
        ->and($metrics['completed_work_orders'])->toBe(1)
        ->and($metrics['mttr_hours'])->toBe(3.0)
        ->and($metrics['average_response_hours'])->toBe(2.0)
        ->and($metrics['first_time_fix_rate'])->toBe(100.0)
        ->and($metrics['by_priority'])->toBe(['high' => 1]);
});

it('rejects incomplete remaining capability records', function () {
    $team = Team::factory()->create();

    expect(fn () => app(CreateCommercialRecord::class)->handle($team->id, ['kind' => 'quote']))
        ->toThrow(ValidationException::class);
});

it('keeps commercial billable lines tenant scoped and synchronizes record totals', function () {
    $team = Team::factory()->create();
    $record = app(CreateCommercialRecord::class)->handle($team->id, ['kind' => 'quote', 'title' => 'Annual service']);
    $line = app(CreateCommercialLine::class)->handle($team->id, $record, ['description' => 'Preventative maintenance', 'quantity' => 2, 'unit_price' => 125.50]);

    expect($line->line_total)->toBe('251.00')
        ->and($record->refresh()->amount)->toBe('251.00')
        ->and($record->lines()->count())->toBe(1);
});

it('exposes commercial billable lines through the tenant API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('commercial-lines-test')->plainTextToken;
    $record = $this->withToken($token)->postJson('/api/v1/maintenance/commercial', ['kind' => 'quote', 'title' => 'Annual service'])->assertCreated()->json('data.id');

    $line = $this->withToken($token)->postJson("/api/v1/maintenance/commercial/{$record}/lines", ['description' => 'Emergency response', 'quantity' => 3, 'unit_price' => 100])->assertCreated()->json('data.id');
    $this->withToken($token)->patchJson("/api/v1/maintenance/commercial/{$record}/lines/{$line}", ['unit_price' => 125])->assertOk()->assertJsonPath('data.attributes.line_total', '375.00');
    $this->withToken($token)->getJson("/api/v1/maintenance/commercial/{$record}/lines")->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->deleteJson("/api/v1/maintenance/commercial/{$record}/lines/{$line}")->assertNoContent();

    expect(CommercialLine::query()->where('commercial_record_id', $record)->count())->toBe(0);
});

it('restricts reporting records to the supported metric families', function () {
    $team = Team::factory()->create();

    expect(ReportKind::options())->toHaveKeys(['backlog', 'response', 'first_time_fix', 'downtime', 'cost', 'utilization', 'stock', 'sla', 'compliance'])
        ->and(fn () => app(CreateReportRecord::class)->handle($team->id, ['kind' => 'arbitrary', 'title' => 'Unsupported metric']))
        ->toThrow(ValidationException::class);
});

it('filters reporting records by kind and overlapping period', function () {
    $team = Team::factory()->create();
    $create = app(CreateReportRecord::class);
    $backlog = $create->handle($team->id, [
        'kind' => 'backlog', 'title' => 'August backlog',
        'period_start' => '2026-08-01 00:00:00', 'period_end' => '2026-08-31 23:59:59',
    ]);
    $create->handle($team->id, [
        'kind' => 'cost', 'title' => 'July costs',
        'period_start' => '2026-07-01 00:00:00', 'period_end' => '2026-07-31 23:59:59',
    ]);

    expect(ReportRecord::query()
        ->where('team_id', $team->id)->ofKind('backlog')->forPeriod('2026-08-15', '2026-08-20')->pluck('id')->all())
        ->toBe([$backlog->id]);
});

it('publishes a draft reporting record through its domain action', function () {
    $team = Team::factory()->create();
    $record = app(CreateReportRecord::class)->handle($team->id, ['kind' => 'backlog', 'title' => 'August backlog']);

    $published = app(PublishReport::class)->execute($team->id, $record);

    expect($published->status)->toBe('published')
        ->and($published->metadata['status_history'][0]['to'])->toBe('published')
        ->and(ReportRecord::query()->published()->whereKey($record->id)->exists())->toBeTrue();
});

it('requires the publish action for report status changes', function () {
    $team = Team::factory()->create();
    $record = app(CreateReportRecord::class)->handle($team->id, ['kind' => 'backlog', 'title' => 'August backlog']);

    expect(fn () => app(UpdateReportRecord::class)->handle($team->id, $record, ['status' => 'published']))
        ->toThrow(ValidationException::class);
});

it('builds a tenant-scoped reporting summary by kind and status', function () {
    $team = Team::factory()->create();
    $create = app(CreateReportRecord::class);
    $create->handle($team->id, ['kind' => 'backlog', 'title' => 'Open', 'metric_value' => 4]);
    $published = $create->handle($team->id, ['kind' => 'backlog', 'title' => 'Closed', 'metric_value' => 2]);
    app(PublishReport::class)->execute($team->id, $published);
    $otherTeam = Team::factory()->create();
    $create->handle($otherTeam->id, ['kind' => 'backlog', 'title' => 'Other tenant', 'metric_value' => 100]);

    $summary = app(BuildReportSummary::class)->handle($team->id);

    expect($summary['total_records'])->toBe(2)
        ->and($summary['published_records'])->toBe(1)
        ->and($summary['draft_records'])->toBe(1)
        ->and($summary['metric_total'])->toBe(6.0)
        ->and($summary['by_kind']['backlog'])->toBe(['count' => 2, 'metric_total' => 6.0]);
});

it('scopes compliance records by expiry', function () {
    $team = Team::factory()->create();
    $create = app(CreateComplianceRecord::class);
    $expired = $create->handle($team->id, ['kind' => 'permit', 'title' => 'Expired permit', 'expires_at' => '2026-01-01']);
    $current = $create->handle($team->id, ['kind' => 'permit', 'title' => 'Current permit', 'expires_at' => '2027-01-01']);

    expect(ComplianceRecord::query()->where('team_id', $team->id)->expired()->whereKey($expired)->exists())->toBeTrue()
        ->and(ComplianceRecord::query()->where('team_id', $team->id)->current()->whereKey($current)->exists())->toBeTrue();
});

it('exposes compliance evidence and corrective-action workflows through the tenant API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('compliance-actions-test')->plainTextToken;
    $record = $this->withToken($token)->postJson('/api/v1/maintenance/compliance', ['kind' => 'incident', 'title' => 'Safety incident'])->assertCreated()->json('data.id');

    $this->withToken($token)->postJson("/api/v1/maintenance/compliance/{$record}/evidence", ['kind' => 'photo', 'label' => 'Damaged guard', 'reference' => 'media/guard.jpg'])->assertCreated();
    $action = $this->withToken($token)->postJson("/api/v1/maintenance/compliance/{$record}/corrective-actions", ['title' => 'Replace guard'])->assertCreated()->json('data.id');
    $this->withToken($token)->postJson("/api/v1/maintenance/compliance/{$record}/corrective-actions/{$action}/complete")
        ->assertOk()->assertJsonPath('data.attributes.status', 'completed');
    $this->withToken($token)->getJson("/api/v1/maintenance/compliance/{$record}/evidence")->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->getJson("/api/v1/maintenance/compliance/{$record}/corrective-actions")->assertOk()->assertJsonPath('data.0.attributes.status', 'completed');
});

it('supports tenant-scoped document approval and versioning through the modular API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('documents-test')->plainTextToken;

    $document = $this->withToken($token)->postJson('/api/v1/maintenance/documents', [
        'name' => 'Safety manual',
        'document_type' => 'manual',
        'file_name' => 'safety.pdf',
    ])->assertCreated()->json('data.id');

    $this->withToken($token)->postJson("/api/v1/maintenance/documents/{$document}/approve")
        ->assertOk()->assertJsonPath('data.attributes.status', 'active');

    $this->withToken($token)->postJson("/api/v1/maintenance/documents/{$document}/versions", [
        'version' => '2.0',
        'file_name' => 'safety-v2.pdf',
        'change_notes' => 'Updated emergency procedure',
    ])->assertCreated()->assertJsonPath('data.attributes.version', '2.0');

    $this->withToken($token)->getJson('/api/v1/maintenance/documents?document_type=manual')
        ->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->getJson("/api/v1/maintenance/documents/{$document}/versions")
        ->assertOk()->assertJsonCount(1, 'data');

    expect(MaintenanceDocument::query()->find($document)->version)->toBe('2.0');
});

it('keeps document domain actions tenant scoped', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $document = app(CreateMaintenanceDocument::class)->handle($team->id, ['name' => 'Permit']);
    app(CreateDocumentVersion::class)->handle($team->id, $document, ['version' => '1.1']);

    expect($document->refresh()->versions)->toHaveCount(1)
        ->and($document->team_id)->toBe($team->id)
        ->and(MaintenanceDocument::query()->where('team_id', $otherTeam->id)->count())->toBe(0);
});

it('exposes tenant-scoped maintenance notes through the modular API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('notes-test')->plainTextToken;

    $note = $this->withToken($token)->postJson('/api/v1/maintenance/notes', [
        'content' => 'Customer requested a morning visit.',
        'noteable_type' => 'maintenance-customer',
        'noteable_id' => 12,
    ])->assertCreated()->json('data.id');

    $this->withToken($token)->getJson('/api/v1/maintenance/notes')->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->patchJson("/api/v1/maintenance/notes/{$note}", ['content' => 'Customer confirmed a morning visit.'])
        ->assertOk()->assertJsonPath('data.attributes.content', 'Customer confirmed a morning visit.');
    $this->withToken($token)->deleteJson("/api/v1/maintenance/notes/{$note}")->assertNoContent();
    expect(MaintenanceNote::withTrashed()->find($note)->deleted_at)->not->toBeNull();
});

it('keeps maintenance note creation tenant scoped', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $note = app(CreateMaintenanceNote::class)->handle($team->id, ['content' => 'Internal note']);

    expect($note->team_id)->toBe($team->id)
        ->and(MaintenanceNote::query()->where('team_id', $otherTeam->id)->exists())->toBeFalse();
});

it('supports tenant-scoped maintenance task assignment and completion', function () {
    $team = Team::factory()->create();
    $task = app(CreateMaintenanceTask::class)->handle($team->id, ['description' => 'Inspect HVAC unit', 'priority' => 2, 'due_date' => now()->addDay()->toDateString()]);
    $completed = app(CompleteMaintenanceTask::class)->handle($team->id, $task);

    expect($completed->status)->toBe('completed')
        ->and($completed->completed_at)->not->toBeNull()
        ->and(MaintenanceTask::query()->where('team_id', $team->id)->completed()->count())->toBe(1);
});

it('exposes maintenance task CRUD and overdue filtering through the tenant API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('tasks-test')->plainTextToken;

    $task = $this->withToken($token)->postJson('/api/v1/maintenance/tasks', [
        'description' => 'Inspect HVAC unit',
        'due_date' => now()->subDay()->toDateString(),
    ])->assertCreated()->json('data.id');

    $this->withToken($token)->getJson('/api/v1/maintenance/tasks?overdue=1')->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->patchJson("/api/v1/maintenance/tasks/{$task}", ['status' => 'in_progress'])
        ->assertOk()->assertJsonPath('data.attributes.status', 'in_progress');
    $this->withToken($token)->postJson("/api/v1/maintenance/tasks/{$task}/complete")
        ->assertOk()->assertJsonPath('data.attributes.status', 'completed');
    $this->withToken($token)->deleteJson("/api/v1/maintenance/tasks/{$task}")->assertNoContent();
});

it('enforces portal request status transitions', function () {
    $team = Team::factory()->create();
    $record = app(CreatePortalRecord::class)->handle($team->id, ['kind' => 'request', 'title' => 'Visit request']);
    $transition = app(TransitionPortalRecord::class);

    $submitted = $transition->handle($team->id, $record, 'submitted');
    expect($submitted->status)->toBe('submitted');
    expect(fn () => $transition->handle($team->id, $submitted, 'resolved'))->toThrow(ValidationException::class);
});

it('enforces commercial record status transitions', function () {
    $team = Team::factory()->create();
    $record = app(CreateCommercialRecord::class)->handle($team->id, ['kind' => 'quote', 'title' => 'Annual contract']);
    $transition = app(TransitionCommercialRecord::class);

    $proposed = $transition->handle($team->id, $record, 'proposed');
    expect($proposed->status)->toBe('proposed');
    expect(fn () => $transition->handle($team->id, $proposed, 'fulfilled'))->toThrow(ValidationException::class);
});

it('tracks tenant-scoped vendor contracts and their expiry lifecycle', function () {
    $team = Team::factory()->create();
    $contract = app(CreateVendorContract::class)->handle($team->id, [
        'vendor_name' => 'Acme Services',
        'contract_number' => 'ACME-2026',
        'title' => 'Pump maintenance agreement',
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    $daysUntilExpiration = $contract->daysUntilExpiration();

    expect($contract)->toBeInstanceOf(VendorContract::class)
        ->and($contract->team_id)->toBe($team->id)
        ->and($daysUntilExpiration)->toBeGreaterThanOrEqual(9)
        ->and($daysUntilExpiration)->toBeLessThanOrEqual(10);

    $active = app(TransitionVendorContract::class)->handle($team->id, $contract, 'active');
    expect($active->isActive())->toBeTrue()
        ->and(VendorContract::query()->where('team_id', $team->id)->expiringSoon(30)->whereKey($contract)->exists())->toBeTrue();
});

it('derives vendor evaluation ratings and keeps evaluations tenant scoped', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $evaluation = app(CreateVendorPerformanceEvaluation::class)->handle($team->id, [
        'vendor_name' => 'Acme Services',
        'evaluation_date' => now()->toDateString(),
        'quality_rating' => 5,
        'timeliness_rating' => 4,
        'communication_rating' => 3,
        'cost_effectiveness_rating' => 4,
        'professionalism_rating' => 5,
    ]);

    expect($evaluation)->toBeInstanceOf(VendorPerformanceEvaluation::class)
        ->and($evaluation->overall_rating)->toBe('4.20')
        ->and(VendorPerformanceEvaluation::query()->where('team_id', $otherTeam->id)->count())->toBe(0)
        ->and(VendorPerformanceEvaluation::query()->where('team_id', $team->id)->highPerformance()->whereKey($evaluation)->exists())->toBeTrue();
});

it('recalculates vendor evaluation ratings through the update action', function () {
    $team = Team::factory()->create();
    $evaluation = app(CreateVendorPerformanceEvaluation::class)->handle($team->id, ['vendor_name' => 'Acme Services', 'evaluation_date' => now()->toDateString(), 'quality_rating' => 2, 'timeliness_rating' => 2]);

    $updated = app(UpdateVendorPerformanceEvaluation::class)->handle($team->id, $evaluation, ['quality_rating' => 5, 'timeliness_rating' => 4, 'communication_rating' => 5]);

    expect($updated->overall_rating)->toBe('4.67');
});
