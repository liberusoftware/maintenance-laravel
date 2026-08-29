<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\DeleteMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource\Pages\CreateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource\Pages\EditMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource\Pages\ListMaintenancePlans;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

class MaintenancePlanResource extends Resource
{
    protected static ?string $model = MaintenancePlan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('code')->required()->maxLength(64), Textarea::make('description')->maxLength(10000), TextInput::make('equipment_id')->numeric()->minValue(1), TextInput::make('assigned_to')->numeric()->minValue(1), TextInput::make('checklist_id')->numeric()->minValue(1), Textarea::make('instructions')->maxLength(10000), TextInput::make('estimated_duration')->numeric()->minValue(0), Select::make('frequency_unit')->options(['hours' => 'Hours', 'days' => 'Days', 'weeks' => 'Weeks', 'months' => 'Months', 'years' => 'Years', 'meters' => 'Meters'])->required(), TextInput::make('frequency_value')->numeric()->minValue(1)->required()]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $t = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $t === null ? $q->whereRaw('1=0') : $q->where('team_id', $t->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('code'), TextColumn::make('assigned_to'), TextColumn::make('frequency_value'), TextColumn::make('frequency_unit'), TextColumn::make('next_due_at')->dateTime()])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(function (MaintenancePlan $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteMaintenancePlan::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMaintenancePlans::route('/'), 'create' => CreateMaintenancePlan::route('/create'), 'edit' => EditMaintenancePlan::route('/{record}/edit')];
    }
}
