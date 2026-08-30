<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Core\Actions\CreateStatus;
use Liberu\Modules\Maintenance\Core\Actions\DeleteStatus;
use Liberu\Modules\Maintenance\Core\Actions\UpdateStatus;
use Liberu\Modules\Maintenance\Core\Models\Status;
use Livewire\Component;

final class StatusList extends Component
{
    public string $name = '';

    public string $code = '';

    public string $color = '';

    public int $sort_order = 0;

    public bool $is_default = false;

    public bool $is_active = true;

    public ?int $editingStatusId = null;

    public function save(CreateStatus $create): void
    {
        $teamId = $this->teamId();
        abort_if($teamId === null, 403, 'A current team context is required.');
        $this->validate($this->rules());
        $create->execute($teamId, $this->attributes());
        $this->cancelEdit();
        $this->dispatch('maintenance-core-status-created');
    }

    public function edit(int $statusId): void
    {
        $status = $this->status($statusId);
        abort_if($status === null, 404);
        $this->editingStatusId = $status->id;
        $this->name = $status->name;
        $this->code = $status->code;
        $this->color = $status->color ?? '';
        $this->sort_order = $status->sort_order;
        $this->is_default = $status->is_default;
        $this->is_active = $status->is_active;
    }

    public function update(UpdateStatus $update): void
    {
        $status = $this->status($this->editingStatusId);
        abort_if($status === null, 404);
        $this->validate($this->rules());
        $update->execute($status, $this->attributes());
        $this->cancelEdit();
        $this->dispatch('maintenance-core-status-updated');
    }

    public function delete(int $statusId, DeleteStatus $delete): void
    {
        $status = $this->status($statusId);
        abort_if($status === null, 404);
        $delete->execute($status);
        $this->cancelEdit();
        $this->dispatch('maintenance-core-status-deleted');
    }

    public function cancelEdit(): void
    {
        $this->editingStatusId = null;
        $this->reset(['name', 'code', 'color', 'sort_order', 'is_default', 'is_active']);
        $this->is_active = true;
    }

    public function render(): View
    {
        $teamId = $this->teamId();
        $statuses = $teamId === null ? collect() : Status::query()->where('team_id', $teamId)->orderBy('sort_order')->orderBy('name')->get();

        return view('module-maintenance-core-livewire::livewire.status-list', compact('statuses'));
    }

    /** @return array<string, mixed> */
    private function attributes(): array
    {
        return ['name' => $this->name, 'code' => $this->code, 'color' => $this->color ?: null, 'sort_order' => $this->sort_order, 'is_default' => $this->is_default, 'is_active' => $this->is_active];
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:32'], 'color' => ['nullable', 'string', 'max:32'], 'sort_order' => ['integer', 'min:0'], 'is_default' => ['boolean'], 'is_active' => ['boolean']];
    }

    private function status(?int $id): ?Status
    {
        $teamId = $this->teamId();

        return $id === null || $teamId === null ? null : Status::query()->where('team_id', $teamId)->find($id);
    }

    private function teamId(): ?int
    {
        $team = auth()->user()?->currentTeam;

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }
}
