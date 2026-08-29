<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Reporting\Filament\Resources;

use Filament\Actions\Action;
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
use Liberu\Modules\Maintenance\Report\Actions\DeleteReportRecord;
use Liberu\Modules\Maintenance\Report\Actions\PublishReport;
use Liberu\Modules\Maintenance\Report\Filament\Resources\ReportingResource\Pages\CreateReport;
use Liberu\Modules\Maintenance\Report\Filament\Resources\ReportingResource\Pages\EditReport;
use Liberu\Modules\Maintenance\Report\Filament\Resources\ReportingResource\Pages\ListReports;
use Liberu\Modules\Maintenance\Report\Models\ReportKind;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;

class ReportingResource extends Resource
{
    protected static ?string $model = ReportRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('kind')->options(ReportKind::options())->required()->searchable(), TextInput::make('title')->required()]);
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
            Action::make('publish')->label('Publish')->visible(fn (ReportRecord $record): bool => $record->status === 'draft')->action(function (ReportRecord $record): void {
                $team = Filament::getTenant() ?? auth()->user()?->currentTeam;
                abort_if($team === null, 403);
                app(PublishReport::class)->execute((int) $team->getKey(), $record);
            }),
            DeleteAction::make()->action(fn (ReportRecord $record) => app(DeleteReportRecord::class)->handle((int) (Filament::getTenant() ?? auth()->user()?->currentTeam)->getKey(), $record)),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReports::route('/'), 'create' => CreateReport::route('/create'), 'edit' => EditReport::route('/{record}/edit')];
    }
}
