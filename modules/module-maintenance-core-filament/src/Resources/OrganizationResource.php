<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Core\Actions\DeleteOrganization;
use Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource\Pages\CreateOrganization;
use Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource\Pages\EditOrganization;
use Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource\Pages\ListOrganizations;
use Liberu\Modules\Maintenance\Core\Models\Organization;

final class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(32),
            Textarea::make('description')->maxLength(10000),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $tenant === null ? $query->whereRaw('1 = 0') : $query->where('team_id', $tenant->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('code')->searchable()->sortable(),
            TextColumn::make('state')->badge(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(fn (Organization $record) => app(DeleteOrganization::class)->execute($record)),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}
