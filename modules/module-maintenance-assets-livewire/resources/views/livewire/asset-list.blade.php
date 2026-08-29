<div>
    <form wire:submit="{{ $editingAssetId === null ? 'save' : 'update' }}">
        <input wire:model="name" placeholder="Asset name">
        <input wire:model="code" placeholder="Code">
        <input wire:model="category" placeholder="Category">
        <input wire:model="location" placeholder="Location">
        <input wire:model="manufacturer" placeholder="Manufacturer">
        <input wire:model="model" placeholder="Model">
        <input wire:model="sensor_type" placeholder="Sensor type (optional)">
        <button type="submit">{{ $editingAssetId === null ? 'Add asset' : 'Update asset' }}</button>
        @if ($editingAssetId !== null)<button type="button" wire:click="cancelEdit">Cancel</button>@endif
    </form>
    @error('name')<p>{{ $message }}</p>@enderror
    @error('code')<p>{{ $message }}</p>@enderror
    <ul>
        @forelse($assets as $asset)
            <li>{{ $asset->name }} ({{ $asset->code }}) <button type="button" wire:click="edit({{ $asset->id }})">Edit</button> <button type="button" wire:click="delete({{ $asset->id }})">Delete</button></li>
        @empty
            <li>No assets yet.</li>
        @endforelse
    </ul>
</div>
