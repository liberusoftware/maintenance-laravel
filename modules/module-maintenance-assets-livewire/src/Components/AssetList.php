<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Actions\DeleteAsset;
use Liberu\Modules\Maintenance\Assets\Actions\UpdateAsset;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Livewire\Component;

class AssetList extends Component
{
    public string $name = '';

    public string $code = '';

    public string $category = '';

    public string $criticality = 'normal';

    public ?int $editingAssetId = null;

    public function save(CreateAsset $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'category' => 'nullable|string|max:255', 'criticality' => 'required|in:normal,high,critical']);
        $create->handle((int) $id, ['name' => $this->name, 'code' => $this->code, 'category' => $this->category, 'criticality' => $this->criticality]);
        $this->reset(['name', 'code', 'category', 'criticality']);
        $this->dispatch('maintenance-assets-created');
    }

    public function edit(int $assetId): void
    {
        $asset = $this->assetForCurrentTeam($assetId);
        $this->editingAssetId = $asset->getKey();
        $this->name = $asset->name;
        $this->code = $asset->code;
        $this->category = (string) ($asset->category ?? '');
        $this->criticality = (string) ($asset->criticality ?? 'normal');
    }

    public function update(UpdateAsset $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingAssetId === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'category' => 'nullable|string|max:255', 'criticality' => 'required|in:normal,high,critical']);
        $update->handle((int) $teamId, $this->assetForCurrentTeam($this->editingAssetId), ['name' => $this->name, 'code' => $this->code, 'category' => $this->category, 'criticality' => $this->criticality]);
        $this->cancelEdit();
        $this->dispatch('maintenance-assets-updated');
    }

    public function delete(int $assetId, DeleteAsset $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->assetForCurrentTeam($assetId));
        $this->dispatch('maintenance-assets-deleted');
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'code', 'category', 'criticality', 'editingAssetId']);
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $assets = $id === null ? collect() : Asset::where('team_id', $id)->orderBy('name')->get();

        return view('module-maintenance-assets-livewire::livewire.asset-list', compact('assets'));
    }

    private function assetForCurrentTeam(int $assetId): Asset
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return Asset::query()->where('team_id', $teamId)->findOrFail($assetId);
    }
}
