<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Compliance\Actions\DeleteComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Filament\Resources\ComplianceResource\Pages\CreateCompliance;
use Liberu\Modules\Maintenance\Compliance\Filament\Resources\ComplianceResource\Pages\EditCompliance;
use Liberu\Modules\Maintenance\Compliance\Filament\Resources\ComplianceResource\Pages\ListCompliance;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;

class ComplianceResource extends Resource
{
    protected static ?string $model = ComplianceRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('kind')->required(), TextInput::make('title')->required(), TextInput::make('status')->default('draft')]);
    }

    public static function getEloquentQuery(): Builder
    {
        $team = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $team === null ? parent::getEloquentQuery()->whereRaw('1=0') : parent::getEloquentQuery()->where('team_id', $team->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('kind'), TextColumn::make('title')->searchable(), TextColumn::make('status')->badge()])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(fn (ComplianceRecord $record) => app(DeleteComplianceRecord::class)->handle((int) (Filament::getTenant() ?? auth()->user()?->currentTeam)->getKey(), $record)),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCompliance::route('/'), 'create' => CreateCompliance::route('/create'), 'edit' => EditCompliance::route('/{record}/edit')];
    }
}
