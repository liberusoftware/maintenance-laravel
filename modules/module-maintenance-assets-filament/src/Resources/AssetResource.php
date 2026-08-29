<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Assets\Actions\DeleteAsset;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource\Pages\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource\Pages\EditAsset;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource\Pages\ListAssets;
use Liberu\Modules\Maintenance\Assets\Models\Asset;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), Textarea::make('description')->maxLength(10000), TextInput::make('code')->required()->maxLength(64), TextInput::make('category')->maxLength(255), TextInput::make('serial_number')->maxLength(255), TextInput::make('model')->maxLength(255), TextInput::make('manufacturer')->maxLength(255), TextInput::make('location')->maxLength(255), DatePicker::make('purchase_date'), DatePicker::make('warranty_expiry')->afterOrEqual('purchase_date'), Textarea::make('notes')->maxLength(10000), TextInput::make('condition')->maxLength(64), TextInput::make('criticality')->maxLength(32), TextInput::make('status')->maxLength(64), TextInput::make('sensor_type')->maxLength(80), TextInput::make('sensor_id')->maxLength(255)]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $t = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $t === null ? $q->whereRaw('1=0') : $q->where('team_id', $t->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('code')->sortable(), TextColumn::make('category'), TextColumn::make('condition')->badge(), TextColumn::make('criticality')->badge(), TextColumn::make('status')->badge(), TextColumn::make('warranty_expiry')->date(), TextColumn::make('health_status')->badge()])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(function (Asset $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteAsset::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAssets::route('/'), 'create' => CreateAsset::route('/create'), 'edit' => EditAsset::route('/{record}/edit')];
    }
}
