<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Inspections\Actions\CompleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspectionTemplate;
use Liberu\Modules\Maintenance\Inspections\Actions\AddInspectionTemplateItem;
use Liberu\Modules\Maintenance\Inspections\Actions\DuplicateInspectionTemplate;
use Liberu\Modules\Maintenance\Inspections\Actions\RemoveInspectionTemplateItem;
use Liberu\Modules\Maintenance\Inspections\Actions\UpdateInspectionTemplateItem;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspectionFollowUp;
use Liberu\Modules\Maintenance\Inspections\Actions\CompleteInspectionFollowUp;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionFollowUp;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

it('creates and completes tenant-scoped inspection follow-ups through the API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Boiler safety check']);
    $token = $user->createToken('inspection-follow-up-test')->plainTextToken;

    $followUp = $this->withToken($token)->postJson("/api/v1/maintenance/inspections/{$inspection->id}/follow-ups", [
        'title' => 'Replace pressure valve',
        'due_at' => now()->addDay()->toISOString(),
    ])->assertCreated()->json('data.id');

    $this->withToken($token)->postJson("/api/v1/maintenance/inspections/follow-ups/{$followUp}/complete")
        ->assertOk()->assertJsonPath('data.attributes.status', 'completed');
    $this->withToken($token)->getJson("/api/v1/maintenance/inspections/{$inspection->id}/follow-ups")
        ->assertOk()->assertJsonPath('data.0.attributes.title', 'Replace pressure valve');
});

it('rejects completing an inspection follow-up twice', function () {
    $team = Team::factory()->create();
    $actor = User::factory()->create();
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Boiler safety check']);
    $followUp = app(CreateInspectionFollowUp::class)->handle($team->id, $inspection, ['title' => 'Replace pressure valve']);
    app(CompleteInspectionFollowUp::class)->handle($team->id, $followUp, $actor->id);

    expect(fn () => app(CompleteInspectionFollowUp::class)->handle($team->id, $followUp, $actor->id))
        ->toThrow(ValidationException::class);
    expect(InspectionFollowUp::query()->where('team_id', $team->id)->open()->count())->toBe(0);
});

it('creates and completes a tenant-scoped inspection', function () {
    $team = Team::factory()->create();
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Pump safety check']);
    $inspection = app(CompleteInspection::class)->handle($team->id, $inspection, 'pass');

    expect($inspection)->toBeInstanceOf(Inspection::class)
        ->and($inspection->team_id)->toBe($team->id)
        ->and($inspection->status)->toBe('completed')
        ->and($inspection->outcome)->toBe('pass');
});

it('creates reusable tenant-scoped inspection templates', function () {
    $team = Team::factory()->create();
    $template = app(CreateInspectionTemplate::class)->handle($team->id, ['key' => 'boiler-safety', 'name' => 'Boiler safety', 'checklist' => ['pressure' => ['type' => 'numeric', 'required' => true]]]);

    expect($template->team_id)->toBe($team->id)
        ->and($template->checklist['pressure']['required'])->toBeTrue()
        ->and($template->is_active)->toBeTrue();
});

it('validates inspection readings against active conditional checklist templates', function () {
    $team = Team::factory()->create();
    app(CreateInspectionTemplate::class)->handle($team->id, [
        'key' => 'boiler-safety',
        'name' => 'Boiler safety',
        'checklist' => [
            'pressure' => ['type' => 'numeric', 'required' => true],
            'pressure_note' => ['type' => 'string', 'when' => ['field' => 'pressure', 'value' => 0]],
            'safe' => ['type' => 'boolean'],
        ],
    ]);
    $create = app(CreateInspection::class);

    expect(fn () => $create->handle($team->id, ['title' => 'Invalid reading', 'template_key' => 'boiler-safety', 'readings' => ['pressure' => 'not numeric']]))
        ->toThrow(ValidationException::class);

    $inspection = $create->handle($team->id, ['title' => 'Boiler safety check', 'template_key' => 'boiler-safety', 'readings' => ['pressure' => 0, 'pressure_note' => 'No pressure', 'safe' => true]]);
    expect(fn () => app(CompleteInspection::class)->handle($team->id, $inspection, 'pass'))
        ->not->toThrow(ValidationException::class);
});

it('requires required inspection readings before completion', function () {
    $team = Team::factory()->create();
    app(CreateInspectionTemplate::class)->handle($team->id, ['key' => 'safety', 'name' => 'Safety', 'checklist' => ['guard' => ['type' => 'boolean', 'required' => true]]]);
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Safety check', 'template_key' => 'safety']);

    expect(fn () => app(CompleteInspection::class)->handle($team->id, $inspection, 'pass'))
        ->toThrow(ValidationException::class);
});

it('rejects an invalid inspection outcome', function () {
    $team = Team::factory()->create();
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Pump safety check']);

    expect(fn () => app(CompleteInspection::class)->handle($team->id, $inspection, 'unknown'))
        ->toThrow(ValidationException::class);
});

it('does not allow a completed inspection to be completed again', function () {
    $team = Team::factory()->create();
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Pump safety check']);
    $inspection = app(CompleteInspection::class)->handle($team->id, $inspection, 'pass');

    expect(fn () => app(CompleteInspection::class)->handle($team->id, $inspection, 'fail'))
        ->toThrow(ValidationException::class);
});

it('provides inspection status, outcome, and date query scopes', function () {
    $team = Team::factory()->create();
    $create = app(CreateInspection::class);
    $draft = $create->handle($team->id, ['title' => 'Draft inspection']);
    $passed = $create->handle($team->id, ['title' => 'Passed inspection', 'inspected_at' => '2026-08-20 10:00:00']);
    app(CompleteInspection::class)->handle($team->id, $passed, 'pass');

    expect(Inspection::query()->where('team_id', $team->id)->draft()->whereKey($draft)->exists())->toBeTrue()
        ->and(Inspection::query()->where('team_id', $team->id)->completed()->withOutcome('pass')->inspectedBetween('2026-08-20', '2026-08-21')->whereKey($passed)->exists())->toBeTrue();
});

it('manages reusable inspection checklist items as modular template definitions', function () {
    $team = Team::factory()->create();
    $template = app(CreateInspectionTemplate::class)->handle($team->id, ['key' => 'pump', 'name' => 'Pump check', 'checklist' => []]);

    app(AddInspectionTemplateItem::class)->handle($team->id, $template, 'pressure', ['type' => 'numeric', 'required' => true]);
    app(UpdateInspectionTemplateItem::class)->handle($team->id, $template->refresh(), 'pressure', ['required' => false]);
    expect($template->refresh()->checklist['pressure']['required'])->toBeFalse();

    app(RemoveInspectionTemplateItem::class)->handle($team->id, $template->refresh(), 'pressure');
    expect($template->refresh()->checklist)->toBe([]);
});

it('duplicates an inspection template without activating the copy', function () {
    $team = Team::factory()->create();
    $template = app(CreateInspectionTemplate::class)->handle($team->id, ['key' => 'safety', 'name' => 'Safety', 'checklist' => ['guard' => ['type' => 'boolean']]]);
    $copy = app(DuplicateInspectionTemplate::class)->handle($team->id, $template, 'Safety copy', 'safety-copy');

    expect($copy->team_id)->toBe($team->id)
        ->and($copy->key)->toBe('safety-copy')
        ->and($copy->is_active)->toBeFalse()
        ->and($copy->checklist)->toBe($template->checklist);
});

it('manages checklist items through the tenant inspection API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('inspection-template-items-test')->plainTextToken;
    $template = $this->withToken($token)->postJson('/api/v1/maintenance/inspections/templates', ['key' => 'boiler', 'name' => 'Boiler', 'checklist' => []])->assertCreated()->json('data.id');

    $this->withToken($token)->postJson("/api/v1/maintenance/inspections/templates/{$template}/items", ['key' => 'temperature', 'type' => 'numeric'])->assertCreated()->assertJsonPath('data.attributes.checklist.temperature.type', 'numeric');
    $this->withToken($token)->patchJson("/api/v1/maintenance/inspections/templates/{$template}/items/temperature", ['required' => true])->assertOk()->assertJsonPath('data.attributes.checklist.temperature.required', true);
    $this->withToken($token)->deleteJson("/api/v1/maintenance/inspections/templates/{$template}/items/temperature")->assertNoContent();
    $this->withToken($token)->postJson("/api/v1/maintenance/inspections/templates/{$template}/duplicate", ['key' => 'boiler-copy'])->assertCreated()->assertJsonPath('data.attributes.key', 'boiler-copy');
});
