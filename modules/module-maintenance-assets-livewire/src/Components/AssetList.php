<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Livewire\Component;

class AssetList extends Component
{
    public string $name = '';

    public string $code = '';

    public string $category = '';

    public function save(CreateAsset $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'category' => 'nullable|string|max:255']);
        $create->handle((int) $id, ['name' => $this->name, 'code' => $this->code, 'category' => $this->category]);
        $this->reset(['name', 'code', 'category']);
        $this->dispatch('maintenance-assets-created');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $assets = $id === null ? collect() : Asset::where('team_id', $id)->orderBy('name')->get();

        return view('module-maintenance-assets-livewire::livewire.asset-list', compact('assets'));
    }
}
