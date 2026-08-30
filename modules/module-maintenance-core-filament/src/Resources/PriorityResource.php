<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Core\Actions\DeletePriority;
use Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource\Pages\CreatePriority;
use Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource\Pages\EditPriority;
use Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource\Pages\ListPriorities;
use Liberu\Modules\Maintenance\Core\Models\Priority;

final class PriorityResource extends Resource
{
    protected static ?string $model = Priority::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(32),
            ColorPicker::make('color'),
            TextInput::make('sort_order')->numeric()->minValue(0)->default(0),
            Toggle::make('is_default'),
            Toggle::make('is_active')->default(true),
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
            TextColumn::make('sort_order')->sortable(),
            IconColumn::make('is_default')->boolean(),
            IconColumn::make('is_active')->boolean(),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(fn (Priority $record) => app(DeletePriority::class)->execute($record)),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListPriorities::route('/'),
            'create' => CreatePriority::route('/create'),
            'edit' => EditPriority::route('/{record}/edit'),
        ];
    }
}
