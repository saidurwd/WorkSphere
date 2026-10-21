<?php

namespace Modules\Todos\Filament\Resources\Todos\RelationManagers;

use App\Enums\LinkType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoLink;
use Modules\Todos\Services\TodoLinkService;

/**
 * The records a To-Do points at (tasks, meetings, obligations, projects, other
 * To-Dos).
 *
 * A link is only SHOWN to a viewer who may see its target: the list is narrowed to
 * {@see TodoLinkService::visibleTargets()}, so a To-Do cannot be used to learn that
 * a record in a module the viewer cannot read exists. Attaching and detaching go
 * through the service and need `update` on the To-Do.
 */
class LinksRelationManager extends RelationManager
{
    protected static string $relationship = 'links';

    protected static ?string $title = 'Links';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('linkable_type')
                ->label('Record type')
                ->options(collect(TodoLinkService::linkableTypes())
                    ->mapWithKeys(fn (string $key): array => [$key => ucfirst(str_replace('_', ' ', $key))])
                    ->all())
                ->required(),
            TextInput::make('linkable_id')->label('Record ID')->numeric()->integer()->minValue(1)->required(),
            Select::make('link_type')
                ->options(collect(LinkType::cases())->mapWithKeys(fn (LinkType $t): array => [$t->value => $t->label()])->all())
                ->default(LinkType::Related->value)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $visible = app(TodoLinkService::class)
                    ->visibleTargets(auth()->user(), $this->getOwnerRecord()->load('links'))
                    ->map(fn (array $row): int => $row['link']->getKey())
                    ->all();

                return $query->whereKey($visible)->with('linkable');
            })
            ->columns([
                TextColumn::make('link_type')->badge()->formatStateUsing(fn (LinkType $state): string => $state->label()),
                TextColumn::make('linkable_type')->label('Type')->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('linkable')->label('Record')->state(fn (TodoLink $record): string => $this->labelFor($record->linkable, $record->linkable_id)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add link')
                    ->using(function (array $data): Model {
                        /** @var Todo $todo */
                        $todo = $this->getOwnerRecord();
                        abort_unless(Gate::allows('update', $todo), 403);

                        try {
                            return app(TodoLinkService::class)->attach(
                                auth()->user(),
                                $todo,
                                $data['linkable_type'],
                                (int) $data['linkable_id'],
                                LinkType::from($data['link_type'] ?? LinkType::Related->value),
                            );
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Remove')
                    ->using(function (TodoLink $record): bool {
                        $todo = $this->getOwnerRecord();
                        abort_unless(Gate::allows('update', $todo), 403);

                        app(TodoLinkService::class)->detach(auth()->user(), $todo, $record->getKey());

                        return true;
                    }),
            ]);
    }

    protected function labelFor(?Model $target, int $id): string
    {
        return (string) ($target?->title ?? $target?->name ?? "#{$id}");
    }
}
