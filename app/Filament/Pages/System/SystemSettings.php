<?php

namespace App\Filament\Pages\System;

use App\Services\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The settings screen.
 *
 * VALIDATION IS PER-TYPE AND DERIVED FROM THE DECLARATION in `Settings::DEFAULTS`,
 * not hand-written per field, so the form cannot disagree with the reader's cast.
 * Only declared keys are rendered, so an undeclared key cannot be created.
 *
 * Setting keys contain dots by design (`app.timezone`), which a state path would
 * read as nesting, so the form fields use {@see fieldName()} instead.
 *
 * @property-read Schema $form
 */
class SystemSettings extends SystemPage implements HasSchemas
{
    use InteractsWithFormActions, InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'system/settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    protected static function ability(): string
    {
        return 'system.settings';
    }

    public static function fieldName(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    public function mount(): void
    {
        $data = [];

        foreach (app(Settings::class)->grouped() as $settings) {
            foreach ($settings as $setting) {
                $data[self::fieldName($setting['key'])] = $setting['value'];
            }
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (app(Settings::class)->grouped() as $group => $settings) {
            $sections[] = Section::make($group)
                ->columns(2)
                ->schema(array_map(fn (array $setting) => $this->field($setting), $settings));
        }

        return $schema->components($sections)->statePath('data');
    }

    /**
     * @param  array{key: string, label: string, description: string, type: string}  $setting
     */
    protected function field(array $setting): TextInput|Toggle
    {
        $name = self::fieldName($setting['key']);

        if ($setting['type'] === 'boolean') {
            return Toggle::make($name)->label($setting['label'])->helperText($setting['description']);
        }

        $input = TextInput::make($name)->label($setting['label'])->helperText($setting['description']);

        return match ($setting['type']) {
            'integer' => $input->numeric()->integer()->required()->minValue(0)->maxValue(1000000),
            'float' => $input->numeric()->required(),
            default => $input->maxLength(255),
        };
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $settings = app(Settings::class);

        foreach (Settings::DEFAULTS as $key => $definition) {
            $value = $state[self::fieldName($key)] ?? null;

            $settings->set($key, match ($definition['type']) {
                'integer' => (int) $value,
                'float' => (float) $value,
                'boolean' => (bool) $value,
                default => is_string($value) ? trim($value) : $value,
            }, auth()->id());
        }

        Notification::make()->title('Settings saved')->success()->send();
    }
}
