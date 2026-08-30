<div>
    <form wire:submit="{{ $editingOrganizationId ? 'update' : 'save' }}" class="space-y-4">
        <div>
            <label for="maintenance-core-name">Name</label>
            <input id="maintenance-core-name" wire:model="name" type="text" required>
            @error('name') <p role="alert">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="maintenance-core-code">Code</label>
            <input id="maintenance-core-code" wire:model="code" type="text" required>
            @error('code') <p role="alert">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="maintenance-core-description">Description</label>
            <textarea id="maintenance-core-description" wire:model="description"></textarea>
            @error('description') <p role="alert">{{ $message }}</p> @enderror
        </div>
        <button type="submit">{{ $editingOrganizationId ? 'Update organization' : 'Create organization' }}</button>
        @if ($editingOrganizationId)
            <button type="button" wire:click="cancelEdit">Cancel</button>
        @endif
    </form>

    <ul aria-label="Organizations">
        @forelse ($organizations as $organization)
            <li wire:key="maintenance-organization-{{ $organization->id }}">
                {{ $organization->name }} ({{ $organization->code }})
                <button type="button" wire:click="edit({{ $organization->id }})">Edit</button>
                <button type="button" wire:click="delete({{ $organization->id }})">Delete</button>
            </li>
        @empty
            <li>No organizations found.</li>
        @endforelse
    </ul>
</div>
