<?php

namespace Modules\Obligations\Filament\Pages;

use App\Enums\WorkItemStatus;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Obligations\Filament\Resources\Obligations\ObligationResource;
use Modules\Tasks\Filament\Resources\Tasks\TaskResource;
use Modules\Tasks\Models\Task;

/**
 * Tasks raised against obligations that I created or am responsible for.
 */
class MyObligationTasks extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'My obligation tasks';

    protected static ?int $navigationSort = 37;

    protected static ?string $title = 'My obligation tasks';

    protected static ?string $slug = 'my-obligation-tasks';

    protected string $view = 'filament.my-obligation-tasks';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Task::query()
                ->whereNotNull('obligation_id')
                ->where(function (Builder $mine): void {
                    $mine->where('user_id', auth()->id())->orWhere('responsible_user_id', auth()->id());
                })
                ->with(['obligation:id,obligation_no,title']))
            ->columns([
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('obligation.title')->label('Obligation')->limit(40),
                TextColumn::make('status')->badge()->formatStateUsing(fn (WorkItemStatus $state): string => $state->label()),
                TextColumn::make('priority')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('due_date')->date()->sortable(),
            ])
            ->defaultSort('due_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options(TaskResource::statusOptions()),
                SelectFilter::make('priority')->options(TaskResource::PRIORITIES),
                Filter::make('timing')
                    ->schema([Select::make('when')->options([
                        'today' => 'Due today',
                        'overdue' => 'Overdue',
                        'upcoming' => 'Upcoming',
                    ])])
                    ->query(function (Builder $query, array $data): Builder {
                        $today = now()->startOfDay();

                        return match ($data['when'] ?? null) {
                            'today' => $query->whereDate('due_date', $today),
                            'overdue' => $query->where('status', '!=', 'completed')->where('due_date', '<', $today),
                            'upcoming' => $query->where('status', '!=', 'completed')->whereDate('due_date', '>', $today),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('task')->label('Task')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record])),
                Action::make('obligation')->label('Obligation')->icon('heroicon-o-document-check')
                    ->url(fn (Task $record): string => ObligationResource::getUrl('view', ['record' => $record->obligation_id]))
                    ->visible(fn (Task $record): bool => $record->obligation !== null),
            ]);
    }
}
