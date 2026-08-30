<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portals\Filament\Resources;

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
use Liberu\Modules\Maintenance\Portal\Actions\DeletePortalRecord;
use Liberu\Modules\Maintenance\Portal\Actions\TransitionPortalRecord;
use Liberu\Modules\Maintenance\Portal\Filament\Resources\PortalsResource\Pages\CreatePortal;
use Liberu\Modules\Maintenance\Portal\Filament\Resources\PortalsResource\Pages\EditPortal;
use Liberu\Modules\Maintenance\Portal\Filament\Resources\PortalsResource\Pages\ListPortals;
use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;

class PortalsResource extends Resource
{
    protected static ?string $model = PortalRecord::class;

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
            Action::make('transition')->label('Change status')->visible(fn (PortalRecord $record): bool => in_array($record->status, ['draft', 'submitted', 'in_progress'], true))->form([TextInput::make('status')->required()])->action(function (PortalRecord $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(TransitionPortalRecord::class)->handle((int) $teamId, $record, $data['status']);
            }),
            DeleteAction::make()->action(fn (PortalRecord $record) => app(DeletePortalRecord::class)->handle((int) (Filament::getTenant() ?? auth()->user()?->currentTeam)->getKey(), $record)),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPortals::route('/'), 'create' => CreatePortal::route('/create'), 'edit' => EditPortal::route('/{record}/edit')];
    }
}
