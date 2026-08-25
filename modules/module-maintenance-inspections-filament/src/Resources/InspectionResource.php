<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages\EditInspection;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages\ListInspections;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('title')->required()->maxLength(255), TextInput::make('template_key')->maxLength(255), Select::make('status')->options(['draft' => 'Draft', 'completed' => 'Completed'])->default('draft'), Select::make('outcome')->options(['pending' => 'Pending', 'pass' => 'Pass', 'fail' => 'Fail', 'conditional' => 'Conditional'])->default('pending')]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $t = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $t === null ? $q->whereRaw('1=0') : $q->where('team_id', $t->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('outcome')->badge(), TextColumn::make('inspected_at')->dateTime()])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListInspections::route('/'), 'create' => CreateInspection::route('/create'), 'edit' => EditInspection::route('/{record}/edit')];
    }
}
