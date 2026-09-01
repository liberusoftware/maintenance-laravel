<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Procurement\Actions\DeletePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\RejectPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\TransitionPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages\CreatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages\EditPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages\ListPurchaseRequests;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

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
            Action::make('reject')->label('Reject')->visible(fn (PurchaseRequest $record): bool => $record->status === 'pending')->form([Textarea::make('reason')->maxLength(2000)])->action(function (PurchaseRequest $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(RejectPurchaseRequest::class)->handle((int) $teamId, $record, (int) auth()->id(), $data['reason'] ?? null);
            }),
            Action::make('transition')->label('Update lifecycle')->visible(fn (PurchaseRequest $record): bool => in_array($record->status, ['pending', 'approved', 'ordered'], true))->form([
                Select::make('status')->options(fn (PurchaseRequest $record): array => match ($record->status) {
                    'pending' => ['cancelled' => 'Cancelled'],
                    'approved' => ['ordered' => 'Ordered', 'cancelled' => 'Cancelled'],
                    'ordered' => ['received' => 'Received', 'cancelled' => 'Cancelled'],
                    default => [],
                })->required(),
            ])->action(function (PurchaseRequest $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(TransitionPurchaseRequest::class)->handle((int) $teamId, $record, $data['status'], auth()->id());
            }),
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
