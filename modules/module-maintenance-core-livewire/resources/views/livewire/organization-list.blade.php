<div>
    <form wire:submit="save" class="space-y-4">
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
        <button type="submit">Create organization</button>
    </form>

    <ul aria-label="Organizations">
        @forelse ($organizations as $organization)
            <li wire:key="maintenance-organization-{{ $organization->id }}">{{ $organization->name }} ({{ $organization->code }})</li>
        @empty
            <li>No organizations found.</li>
        @endforelse
    </ul>
</div>
