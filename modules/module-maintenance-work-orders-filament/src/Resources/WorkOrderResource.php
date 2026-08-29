<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Filament\Resources;

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
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderDependency;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderEvidence;
use Liberu\Modules\Maintenance\WorkOrders\Actions\DeleteWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource\Pages\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource\Pages\ListWorkOrders;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('title')->required()->maxLength(255), Textarea::make('description')->maxLength(10000), TextInput::make('location')->maxLength(255), TextInput::make('equipment_id')->numeric()->minValue(1), TextInput::make('customer_id')->numeric()->minValue(1), TextInput::make('vendor_id')->numeric()->minValue(1), TextInput::make('assigned_to')->numeric()->minValue(1), TextInput::make('guest_name')->maxLength(255), TextInput::make('guest_email')->email()->maxLength(255), TextInput::make('guest_phone')->maxLength(64), TextInput::make('submitted_at')->type('datetime-local'), TextInput::make('reviewed_by')->numeric()->minValue(1), TextInput::make('reviewed_at')->type('datetime-local'), TextInput::make('due_date')->type('datetime-local'), TextInput::make('estimated_minutes')->numeric()->minValue(0), TextInput::make('actual_minutes')->numeric()->minValue(0), Textarea::make('notes')->maxLength(10000), Select::make('priority')->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'])->default('normal')->required(), Select::make('status')->options(['requested' => 'Requested', 'triaged' => 'Triaged', 'in_progress' => 'In progress', 'blocked' => 'Blocked', 'completed' => 'Completed', 'cancelled' => 'Cancelled'])->default('requested')->required()]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $t = Filament::getTenant() ?? auth()->user()?->currentTeam;

        return $t === null ? $q->whereRaw('1=0') : $q->where('team_id', $t->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('number')->sortable(), TextColumn::make('title')->searchable(), TextColumn::make('priority')->badge(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->recordActions([
            EditAction::make(),
            Action::make('addDependency')->label('Add dependency')->form([Select::make('depends_on_work_order_id')->label('Depends on')->options(function (WorkOrder $record): array {
                $teamId = (Filament::getTenant() ?? auth()->user()?->currentTeam)?->getKey();

                return WorkOrder::query()->where('team_id', $teamId)->whereKeyNot($record->getKey())->orderBy('number')->pluck('title', 'id')->all();
            })->required()->searchable()])->action(function (WorkOrder $record, array $data): void {
                $teamId = (Filament::getTenant() ?? auth()->user()?->currentTeam)?->getKey();
                abort_if($teamId === null, 403);
                app(AddWorkOrderDependency::class)->handle((int) $teamId, $record, WorkOrder::query()->findOrFail($data['depends_on_work_order_id']));
            }),
            Action::make('addEvidence')->label('Add evidence')->form([
                TextInput::make('kind')->required()->maxLength(64),
                TextInput::make('label')->required()->maxLength(255),
                TextInput::make('reference')->required()->maxLength(10000),
            ])->action(function (WorkOrder $record, array $data): void {
                $teamId = (Filament::getTenant() ?? auth()->user()?->currentTeam)?->getKey();
                abort_if($teamId === null, 403);
                app(AddWorkOrderEvidence::class)->handle((int) $teamId, $record, array_merge($data, ['added_by' => auth()->id()]));
            }),
            DeleteAction::make()->action(function (WorkOrder $record): void {
                $teamId = auth()->user()?->currentTeam?->getKey();
                abort_if($teamId === null, 403);
                app(DeleteWorkOrder::class)->handle((int) $teamId, $record);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListWorkOrders::route('/'), 'create' => CreateWorkOrder::route('/create'), 'edit' => EditWorkOrder::route('/{record}/edit')];
    }
}
