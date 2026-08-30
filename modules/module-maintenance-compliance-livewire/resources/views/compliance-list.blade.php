<div>
    <form wire:submit="{{ $editingRecordId === null ? 'save' : 'update' }}">
        <input wire:model="kind" type="text" required>
        <input wire:model="title" type="text" required>
        <textarea wire:model="description"></textarea>
        <input wire:model="status" type="text" required>
        <input wire:model="expiresAt" type="datetime-local">
        <button type="submit">{{ $editingRecordId === null ? 'Save' : 'Update' }}</button>
        @if ($editingRecordId !== null)<button type="button" wire:click="cancelEdit">Cancel</button>@endif
    </form>
    <ul>
        @foreach ($records as $record)
            <li>{{ $record->title }} ({{ $record->status }}) <button type="button" wire:click="edit({{ $record->id }})">Edit</button> <button type="button" wire:click="delete({{ $record->id }})">Delete</button></li>
        @endforeach
    </ul>
</div>
