<div>
    <form wire:submit="save">
        <input wire:model="kind" type="text" required>
        <input wire:model="title" type="text" required>
        <button type="submit">Save</button>
    </form>
    <ul>
        @foreach ($records as $record)
            <li>{{ $record->title }} ({{ $record->status }})</li>
        @endforeach
    </ul>
</div>

