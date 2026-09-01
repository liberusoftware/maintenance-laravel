<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Core\Actions\DeleteServiceSetting;
use Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource\Pages\CreateServiceSetting;
use Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource\Pages\EditServiceSetting;
use Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource\Pages\ListServiceSettings;
use Liberu\Modules\Maintenance\Core\Models\ServiceSetting;

final class ServiceSettingResource extends Resource
{
    protected static ?string $model = ServiceSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->required()->maxLength(128)->disabledOn('edit'),
            Textarea::make('value')->maxLength(10000),
            Toggle::make('is_encrypted'),
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
            TextColumn::make('key')->searchable()->sortable(),
            TextColumn::make('value')->formatStateUsing(fn (?string $state, ServiceSetting $record): string => $record->is_encrypted ? '[encrypted]' : (string) $state),
            IconColumn::make('is_encrypted')->boolean(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(fn (ServiceSetting $record) => app(DeleteServiceSetting::class)->execute($record)),
        ]);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListServiceSettings::route('/'),
            'create' => CreateServiceSetting::route('/create'),
            'edit' => EditServiceSetting::route('/{record}/edit'),
        ];
    }
}
