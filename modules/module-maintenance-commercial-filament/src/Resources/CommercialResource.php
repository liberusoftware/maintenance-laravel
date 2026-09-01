<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Commercial\Actions\DeleteCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Actions\TransitionCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Filament\Resources\CommercialResource\Pages\CreateCommercial;
use Liberu\Modules\Maintenance\Commercial\Filament\Resources\CommercialResource\Pages\EditCommercial;
use Liberu\Modules\Maintenance\Commercial\Filament\Resources\CommercialResource\Pages\ListCommercial;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

class CommercialResource extends Resource
{
    protected static ?string $model = CommercialRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

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
            Action::make('transition')->label('Change status')->visible(fn (CommercialRecord $record): bool => in_array($record->status, ['draft', 'proposed', 'approved'], true))->form([TextInput::make('status')->required()])->action(function (CommercialRecord $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(TransitionCommercialRecord::class)->handle((int) $teamId, $record, $data['status']);
            }),
            DeleteAction::make()->action(fn (CommercialRecord $record) => app(DeleteCommercialRecord::class)->handle((int) (Filament::getTenant() ?? auth()->user()?->currentTeam)->getKey(), $record)),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommercial::route('/'), 'create' => CreateCommercial::route('/create'), 'edit' => EditCommercial::route('/{record}/edit')];
    }
}
