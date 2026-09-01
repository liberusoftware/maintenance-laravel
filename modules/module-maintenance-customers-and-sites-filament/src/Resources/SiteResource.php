<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources;

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
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\SiteResource\Pages\CreateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\SiteResource\Pages\EditSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\SiteResource\Pages\ListSites;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'Customers & sites';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->options(fn (): array => Customer::query()->where('team_id', (Filament::getTenant() ?? auth()->user()?->currentTeam)?->getKey())->orderBy('name')->pluck('name', 'id')->all())->required()->searchable(),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(64),
            TextInput::make('address')->maxLength(10000),
            TextInput::make('access_details')->maxLength(10000),
            TextInput::make('hazards')->maxLength(10000),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $team = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $team === null ? $query->whereRaw('1=0') : $query->where('team_id', $team->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('code')->sortable(), TextColumn::make('customer.name')->label('Customer')->searchable(), TextColumn::make('is_active')->label('Active')->badge()])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(function (Site $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteSite::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSites::route('/'), 'create' => CreateSite::route('/create'), 'edit' => EditSite::route('/{record}/edit')];
    }
}
