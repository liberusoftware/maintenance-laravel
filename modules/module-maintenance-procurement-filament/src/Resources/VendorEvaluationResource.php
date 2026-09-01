<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Liberu\Modules\Maintenance\Procurement\Actions\DeleteVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource\Pages\CreateVendorEvaluation;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource\Pages\EditVendorEvaluation;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource\Pages\ListVendorEvaluations;
use Liberu\Modules\Maintenance\Procurement\Models\VendorPerformanceEvaluation;

class VendorEvaluationResource extends Resource
{
    protected static ?string $model = VendorPerformanceEvaluation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|\UnitEnum|null $navigationGroup = 'Procurement';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('vendor_name')->required()->maxLength(255), DatePicker::make('evaluation_date')->required(), TextInput::make('quality_rating')->numeric()->minValue(0)->maxValue(5), TextInput::make('timeliness_rating')->numeric()->minValue(0)->maxValue(5), TextInput::make('communication_rating')->numeric()->minValue(0)->maxValue(5), TextInput::make('cost_effectiveness_rating')->numeric()->minValue(0)->maxValue(5), TextInput::make('professionalism_rating')->numeric()->minValue(0)->maxValue(5)]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $tenant === null ? $query->whereRaw('1=0') : $query->where('team_id', $tenant->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('vendor_name')->searchable(), TextColumn::make('evaluation_date')->date()->sortable(), TextColumn::make('overall_rating')->sortable(), TextColumn::make('would_recommend')->boolean()])->recordActions([
            EditAction::make(),
            DeleteAction::make()->action(function (VendorPerformanceEvaluation $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteVendorPerformanceEvaluation::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListVendorEvaluations::route('/'), 'create' => CreateVendorEvaluation::route('/create'), 'edit' => EditVendorEvaluation::route('/{record}/edit')];
    }
}
