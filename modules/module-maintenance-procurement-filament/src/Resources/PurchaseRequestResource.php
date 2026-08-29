<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Procurement\Actions\DeletePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages\CreatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages\EditPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages\ListPurchaseRequests;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('supplier_name')->maxLength(255), TextInput::make('title')->required()->maxLength(255), Textarea::make('description')->maxLength(10000), TextInput::make('amount')->numeric()->minValue(0)->required(), TextInput::make('currency')->default('USD')->maxLength(3)]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $t = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $t === null ? $q->whereRaw('1=0') : $q->where('team_id', $t->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')->searchable(), TextColumn::make('supplier_name'), TextColumn::make('amount'), TextColumn::make('currency'), TextColumn::make('status')->badge()])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(function (PurchaseRequest $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeletePurchaseRequest::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPurchaseRequests::route('/'), 'create' => CreatePurchaseRequest::route('/create'), 'edit' => EditPurchaseRequest::route('/{record}/edit')];
    }
}
