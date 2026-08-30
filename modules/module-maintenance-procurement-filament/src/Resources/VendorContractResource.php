<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Procurement\Actions\DeleteVendorContract;
use Liberu\Modules\Maintenance\Procurement\Actions\TransitionVendorContract;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource\Pages\CreateVendorContract;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource\Pages\EditVendorContract;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource\Pages\ListVendorContracts;
use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;

class VendorContractResource extends Resource
{
    protected static ?string $model = VendorContract::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('vendor_name')->required()->maxLength(255), TextInput::make('contract_number')->required()->maxLength(255), TextInput::make('title')->required()->maxLength(255), Select::make('contract_type')->options(['service' => 'Service', 'maintenance' => 'Maintenance', 'supply' => 'Supply', 'other' => 'Other'])->default('service')->required(), DatePicker::make('start_date')->required(), DatePicker::make('end_date')->required(), TextInput::make('contract_value')->numeric()->minValue(0), TextInput::make('currency')->default('USD')->length(3), Select::make('status')->options(['draft' => 'Draft', 'active' => 'Active', 'expired' => 'Expired', 'terminated' => 'Terminated', 'renewed' => 'Renewed'])->default('draft')->required()]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $tenant === null ? $query->whereRaw('1=0') : $query->where('team_id', $tenant->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('vendor_name')->searchable(), TextColumn::make('contract_number'), TextColumn::make('title')->searchable(), TextColumn::make('end_date')->date(), TextColumn::make('status')->badge()])->recordActions([
            EditAction::make(),
            Action::make('transition')->label('Update lifecycle')->visible(fn (VendorContract $record): bool => $record->status !== 'terminated')->form([Select::make('status')->options(fn (VendorContract $record): array => match ($record->status) {
                'draft' => ['active' => 'Active', 'terminated' => 'Terminated'], 'active' => ['expired' => 'Expired', 'renewed' => 'Renewed', 'terminated' => 'Terminated'], 'expired' => ['renewed' => 'Renewed', 'terminated' => 'Terminated'], 'renewed' => ['active' => 'Active', 'terminated' => 'Terminated'], default => []
            })->required()])->action(function (VendorContract $record, array $data): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(TransitionVendorContract::class)->handle((int) $teamId, $record, $data['status']);
            }),
            DeleteAction::make()->action(function (VendorContract $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteVendorContract::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListVendorContracts::route('/'), 'create' => CreateVendorContract::route('/create'), 'edit' => EditVendorContract::route('/{record}/edit')];
    }
}
