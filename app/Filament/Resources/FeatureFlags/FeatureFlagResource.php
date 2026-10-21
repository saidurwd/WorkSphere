<?php

namespace App\Filament\Resources\FeatureFlags;

use App\Filament\Resources\FeatureFlags\Pages\CreateFeatureFlag;
use App\Filament\Resources\FeatureFlags\Pages\EditFeatureFlag;
use App\Filament\Resources\FeatureFlags\Pages\ListFeatureFlags;
use App\Models\FeatureFlag;
use App\Services\FeatureFlags;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The feature-flag screen.
 *
 * Every write goes through {@see FeatureFlags::put()} / `delete()`, never the
 * model, so the flag cache is flushed and the actor is recorded.
 *
 * TOGGLING IS A SEPARATE ACTION from saving, because turning a flag ON is the
 * action with consequences and deserves its own button and confirmation. A new
 * flag is created OFF.
 */
class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static ?string $recordTitleAttribute = 'key';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return Gate::allows('system.flags');
    }

    public static function canCreate(): bool
    {
        return Gate::allows('system.flags');
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('system.flags');
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('system.flags');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // A flag key becomes an attribute on the flag object and is used in cache
            // keys, so it is held to an identifier shape rather than free text.
            TextInput::make('key')
                ->required()
                ->maxLength(100)
                ->regex('/^[a-z0-9]+([._-][a-z0-9]+)*$/')
                ->validationMessages(['regex' => 'Use lowercase letters, numbers, dots, dashes or underscores.'])
                ->unique(ignoreRecord: true)
                ->disabledOn('edit')
                ->dehydrated(fn (string $operation): bool => $operation === 'create'),
            TextInput::make('name')->required()->maxLength(150),
            Textarea::make('description')->maxLength(1000)->columnSpanFull(),
            Select::make('type')
                ->options(['boolean' => 'Boolean', 'integer' => 'Integer', 'float' => 'Float', 'string' => 'String', 'json' => 'JSON'])
                ->default('boolean')
                ->required(),
            TextInput::make('value')
                ->formatStateUsing(fn (mixed $state): ?string => match (true) {
                    $state === null => null,
                    is_bool($state) => $state ? 'true' : 'false',
                    is_array($state) => json_encode($state),
                    default => (string) $state,
                }),
            TextInput::make('rollout_percentage')
                ->numeric()->integer()->minValue(0)->maxValue(100)->default(100)->required()->suffix('%'),
            // Empty means everyone; an empty list would mean nobody, which would
            // silently disable the flag the moment somebody cleared the field.
            TagsInput::make('target_roles')
                ->helperText('Role slugs the flag applies to. Leave empty for everyone.'),
        ]);
    }

    /**
     * Coerce the typed value to the flag's declared type.
     */
    public static function castValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return $type === 'boolean' ? true : null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => is_string($value) ? (json_decode($value, true) ?? $value) : $value,
            default => (string) $value,
        };
    }

    /**
     * The attributes {@see FeatureFlags::put()} expects, from the form state.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function payload(array $data, bool $isEnabled): array
    {
        $roles = array_values(array_filter($data['target_roles'] ?? []));

        return [
            ...$data,
            'value' => static::castValue($data['value'] ?? null, $data['type'] ?? 'boolean'),
            'target_roles' => $roles === [] ? null : $roles,
            'is_enabled' => $isEnabled,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('type')->badge(),
                IconColumn::make('is_enabled')->label('On')->boolean(),
                TextColumn::make('rollout_percentage')->label('Rollout')->suffix('%'),
                TextColumn::make('target_roles')->badge()->placeholder('Everyone'),
            ])
            ->defaultSort('key')
            ->recordActions([
                Action::make('toggle')
                    ->label(fn (FeatureFlag $record): string => $record->is_enabled ? 'Turn off' : 'Turn on')
                    ->icon(fn (FeatureFlag $record): string => $record->is_enabled ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->requiresConfirmation()
                    ->action(function (FeatureFlag $record): void {
                        abort_unless(Gate::allows('system.flags'), 403);

                        $enable = ! $record->is_enabled;

                        app(FeatureFlags::class)->put([
                            'key' => $record->key,
                            'name' => $record->name,
                            'description' => $record->description,
                            'type' => $record->type,
                            'value' => $record->value,
                            'rollout_percentage' => $record->rollout_percentage,
                            'variants' => $record->variants,
                            'target_roles' => $record->target_roles,
                            'is_enabled' => $enable,
                        ], auth()->id());

                        Notification::make()->title("Flag [{$record->key}] is now ".($enable ? 'ON' : 'OFF'))->success()->send();
                    }),
                EditAction::make(),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Deleting a flag returns every call site to its stated default.')
                    ->action(function (FeatureFlag $record): void {
                        abort_unless(Gate::allows('system.flags'), 403);

                        app(FeatureFlags::class)->delete($record->key);

                        Notification::make()->title("Flag [{$record->key}] deleted")->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeatureFlags::route('/'),
            'create' => CreateFeatureFlag::route('/create'),
            'edit' => EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}
