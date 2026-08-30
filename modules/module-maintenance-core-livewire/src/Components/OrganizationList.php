<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Core\Actions\CreateOrganization;
use Liberu\Modules\Maintenance\Core\Actions\DeleteOrganization;
use Liberu\Modules\Maintenance\Core\Actions\UpdateOrganization;
use Liberu\Modules\Maintenance\Core\Models\Organization;
use Livewire\Component;

final class OrganizationList extends Component
{
    public string $name = '';

    public string $code = '';

    public string $description = '';

    public ?int $editingOrganizationId = null;

    public function save(CreateOrganization $create): void
    {
        $teamId = $this->teamId();
        abort_if($teamId === null, 403, 'A current team context is required.');
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:10000'],
        ]);
        $create->execute($teamId, $this->name, $this->code, $this->description ?: null);
        $this->reset(['name', 'code', 'description']);
        $this->dispatch('maintenance-core-organization-created');
    }

    public function edit(int $organizationId): void
    {
        $organization = $this->organization($organizationId);
        abort_if($organization === null, 404);
        $this->editingOrganizationId = $organization->id;
        $this->name = $organization->name;
        $this->code = $organization->code;
        $this->description = $organization->description ?? '';
    }

    public function update(UpdateOrganization $update): void
    {
        $organization = $this->organization($this->editingOrganizationId);
        abort_if($organization === null, 404);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:10000'],
        ]);
        $update->execute($organization, ['name' => $this->name, 'code' => $this->code, 'description' => $this->description ?: null]);
        $this->cancelEdit();
        $this->dispatch('maintenance-core-organization-updated');
    }

    public function delete(int $organizationId, DeleteOrganization $delete): void
    {
        $organization = $this->organization($organizationId);
        abort_if($organization === null, 404);
        $delete->execute($organization);
        $this->cancelEdit();
        $this->dispatch('maintenance-core-organization-deleted');
    }

    public function cancelEdit(): void
    {
        $this->editingOrganizationId = null;
        $this->reset(['name', 'code', 'description']);
    }

    public function render(): View
    {
        $teamId = $this->teamId();
        $organizations = $teamId === null
            ? collect()
            : Organization::query()->where('team_id', $teamId)->orderBy('name')->get();

        return view('module-maintenance-core-livewire::livewire.organization-list', compact('organizations'));
    }

    private function teamId(): ?int
    {
        $team = auth()->user()?->currentTeam;

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }

    private function organization(?int $id): ?Organization
    {
        $teamId = $this->teamId();

        return $id === null || $teamId === null ? null : Organization::query()->where('team_id', $teamId)->find($id);
    }
}
