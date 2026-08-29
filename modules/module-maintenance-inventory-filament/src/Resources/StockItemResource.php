<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Filament\Resources;

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
use Liberu\Modules\Maintenance\Inventory\Actions\DeleteStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\IssueStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReleaseReservedStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReserveStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReturnStock;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource\Pages\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource\Pages\EditStockItem;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource\Pages\ListStockItems;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

class StockItemResource extends Resource
{
    protected static ?string $model = StockItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('part_number')->required()->maxLength(96), TextInput::make('name')->required()->maxLength(255), TextInput::make('description')->maxLength(10000), TextInput::make('category')->maxLength(255), TextInput::make('location')->maxLength(255), TextInput::make('supplier_name')->maxLength(255), TextInput::make('lead_time_days')->numeric()->minValue(0), TextInput::make('quantity')->numeric()->minValue(0), TextInput::make('reorder_level')->numeric()->minValue(0), TextInput::make('reorder_quantity')->numeric()->minValue(0), TextInput::make('unit')->maxLength(32), TextInput::make('unit_cost')->numeric()->minValue(0), TextInput::make('notes')->maxLength(10000)]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $t = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $t === null ? $q->whereRaw('1=0') : $q->where('team_id', $t->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('part_number')->searchable(), TextColumn::make('name')->searchable(), TextColumn::make('category'), TextColumn::make('supplier_name'), TextColumn::make('location'), TextColumn::make('quantity')->sortable(), TextColumn::make('reserved_quantity')->sortable(), TextColumn::make('reorder_level'), TextColumn::make('unit_cost')])->recordActions([
            EditAction::make(),
            Action::make('reserve')->label('Reserve')->form([TextInput::make('quantity')->numeric()->minValue(1)->required()])->action(function (StockItem $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(ReserveStock::class)->handle((int) $teamId, $record, (int) $data['quantity']);
            }),
            Action::make('release')->label('Release')->form([TextInput::make('quantity')->numeric()->minValue(1)->required()])->action(function (StockItem $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(ReleaseReservedStock::class)->handle((int) $teamId, $record, (int) $data['quantity']);
            }),
            Action::make('issue')->label('Issue')->form([TextInput::make('quantity')->numeric()->minValue(1)->required(), TextInput::make('notes')->maxLength(10000)])->action(function (StockItem $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(IssueStock::class)->handle((int) $teamId, $record, (int) $data['quantity'], auth()->id(), $data['notes'] ?? null);
            }),
            Action::make('return')->label('Return')->form([TextInput::make('quantity')->numeric()->minValue(1)->required(), TextInput::make('notes')->maxLength(10000)])->action(function (StockItem $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(ReturnStock::class)->handle((int) $teamId, $record, (int) $data['quantity'], auth()->id(), $data['notes'] ?? null);
            }),
            DeleteAction::make()->action(function (StockItem $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteStockItem::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListStockItems::route('/'), 'create' => CreateStockItem::route('/create'), 'edit' => EditStockItem::route('/{record}/edit')];
    }
}
