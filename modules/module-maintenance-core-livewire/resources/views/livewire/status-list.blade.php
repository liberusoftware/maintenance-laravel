<div>
    <form wire:submit="{{ $editingStatusId ? 'update' : 'save' }}" class="space-y-4">
        <input wire:model="name" type="text" placeholder="Name" required>
        @error('name') <p role="alert">{{ $message }}</p> @enderror
        <input wire:model="code" type="text" placeholder="Code" required>
        @error('code') <p role="alert">{{ $message }}</p> @enderror
        <input wire:model="color" type="text" placeholder="Color">
        <input wire:model="sort_order" type="number" min="0">
        <label><input wire:model="is_default" type="checkbox"> Default</label>
        <label><input wire:model="is_active" type="checkbox"> Active</label>
        <button type="submit">{{ $editingStatusId ? 'Update status' : 'Create status' }}</button>
        @if ($editingStatusId)
            <button type="button" wire:click="cancelEdit">Cancel</button>
        @endif
    </form>

    <ul aria-label="Statuses">
        @forelse ($statuses as $status)
            <li wire:key="maintenance-status-{{ $status->id }}">
                {{ $status->name }} ({{ $status->code }})
                <button type="button" wire:click="edit({{ $status->id }})">Edit</button>
                <button type="button" wire:click="delete({{ $status->id }})">Delete</button>
            </li>
        @empty
            <li>No statuses found.</li>
        @endforelse
    </ul>
</div>
