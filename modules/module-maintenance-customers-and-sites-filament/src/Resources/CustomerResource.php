<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources;

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
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\CustomerResource\Pages\EditCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\CustomerResource\Pages\ListCustomers;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Customers & sites';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('code')->required()->maxLength(64), Select::make('type')->options(['customer' => 'Customer', 'vendor' => 'Vendor', 'supplier' => 'Supplier', 'both' => 'Customer and supplier'])->default('customer')->required(), TextInput::make('email')->email()->maxLength(255), TextInput::make('phone')->maxLength(64), Textarea::make('address')->maxLength(10000), TextInput::make('city')->maxLength(255), TextInput::make('state')->maxLength(255), TextInput::make('zip')->maxLength(32), TextInput::make('website')->url()->maxLength(255), TextInput::make('industry')->maxLength(255), Textarea::make('description')->maxLength(10000), TextInput::make('contact_person')->maxLength(255), TextInput::make('payment_terms')->maxLength(255), Textarea::make('notes')->maxLength(10000)]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $t = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $t === null ? $q->whereRaw('1=0') : $q->where('team_id', $t->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('code')->sortable(), TextColumn::make('type')->badge(), TextColumn::make('email')])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(function (Customer $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteCustomer::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCustomers::route('/'), 'create' => CreateCustomer::route('/create'), 'edit' => EditCustomer::route('/{record}/edit')];
    }
}
