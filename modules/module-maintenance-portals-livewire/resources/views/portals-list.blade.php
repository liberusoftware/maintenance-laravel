<div>
    <form wire:submit="{{ $editingRecordId === null ? 'save' : 'update' }}">
        <input wire:model="kind" type="text" required>
        <input wire:model="title" type="text" required>
        <button type="submit">{{ $editingRecordId === null ? 'Save' : 'Update' }}</button>
        @if ($editingRecordId !== null)<button type="button" wire:click="cancelEdit">Cancel</button>@endif
    </form>
    <ul>
        @foreach ($records as $record)
            <li>{{ $record->title }} ({{ $record->status }}) <button type="button" wire:click="edit({{ $record->id }})">Edit</button> @if ($record->status === 'draft')<button type="button" wire:click="transition({{ $record->id }}, 'submitted')">Submit</button>@elseif ($record->status === 'submitted')<button type="button" wire:click="transition({{ $record->id }}, 'in_progress')">Start</button>@elseif ($record->status === 'in_progress')<button type="button" wire:click="transition({{ $record->id }}, 'resolved')">Resolve</button>@endif <button type="button" wire:click="delete({{ $record->id }})">Delete</button></li>
        @endforeach
    </ul>
</div>
