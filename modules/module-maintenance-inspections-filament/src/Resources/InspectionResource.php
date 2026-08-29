<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Inspections\Actions\DeleteInspection;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages\EditInspection;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages\ListInspections;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

final class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('title')->required()->maxLength(255), TextInput::make('template_key')->maxLength(255), TextInput::make('inspector_id')->numeric()->minValue(1), TextInput::make('inspected_at')->type('datetime-local'), Textarea::make('readings')->maxLength(10000), Textarea::make('failures')->maxLength(10000), Textarea::make('signature')->maxLength(10000), TextInput::make('certificate')->maxLength(255), Textarea::make('follow_up')->maxLength(10000)]);
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $tenant === null ? parent::getEloquentQuery()->whereRaw('1=0') : parent::getEloquentQuery()->where('team_id', $tenant->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('outcome')->badge(), TextColumn::make('inspected_at')->dateTime()])->recordActions([EditAction::make(), DeleteAction::make()->action(function (Inspection $record): void {
            $teamId = (Filament::getTenant() ?? auth()->user()?->currentTeam)?->getKey();
            abort_if($teamId === null, 403);
            app(DeleteInspection::class)->handle((int) $teamId, $record);
        })]);
    }

    public static function getPages(): array
    {
        return ['index' => ListInspections::route('/'), 'create' => CreateInspection::route('/create'), 'edit' => EditInspection::route('/{record}/edit')];
    }
}
