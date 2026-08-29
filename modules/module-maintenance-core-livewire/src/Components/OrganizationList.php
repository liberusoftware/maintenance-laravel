<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Core\Actions\CreateOrganization;
use Liberu\Modules\Maintenance\Core\Models\Organization;
use Livewire\Component;

final class OrganizationList extends Component
{
    public string $name = '';

    public string $code = '';

    public string $description = '';

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
}
