<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\DeleteTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\RejectTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource\Pages\CreateTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource\Pages\EditTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource\Pages\ListTimeEntries;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Textarea::make('description')->maxLength(255), TextInput::make('minutes')->numeric()->minValue(1)->required(), TextInput::make('rate')->numeric()->minValue(0), TextInput::make('expense_amount')->numeric()->minValue(0), DateTimePicker::make('started_at'), DateTimePicker::make('ended_at')->after('started_at')]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $t = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $t === null ? $q->whereRaw('1=0') : $q->where('team_id', $t->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('description'), TextColumn::make('minutes'), TextColumn::make('rate'), TextColumn::make('status')->badge()])->recordActions([
            EditAction::make(),
            Action::make('reject')->label('Reject')->visible(fn (TimeEntry $record): bool => $record->status === 'pending')->form([Textarea::make('reason')->maxLength(2000)])->action(function (TimeEntry $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(RejectTimeEntry::class)->handle((int) $teamId, $record, (int) auth()->id(), $data['reason'] ?? null);
            }),
            DeleteAction::make()->action(function (TimeEntry $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteTimeEntry::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTimeEntries::route('/'), 'create' => CreateTimeEntry::route('/create'), 'edit' => EditTimeEntry::route('/{record}/edit')];
    }
}
