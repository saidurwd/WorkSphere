<?php

namespace Modules\Meetings\Filament\Resources\Templates;

use App\Enums\Priority;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Services\ActivityLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;
use Modules\Meetings\Filament\Resources\Templates\Pages\CreateMeetingTemplate;
use Modules\Meetings\Filament\Resources\Templates\Pages\EditMeetingTemplate;
use Modules\Meetings\Filament\Resources\Templates\Pages\ListMeetingTemplates;
use Modules\Meetings\Filament\Resources\Templates\Pages\ViewMeetingTemplate;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingTemplate;
use Modules\Meetings\Models\MeetingType;
use Modules\Meetings\Services\MeetingNumberService;

/**
 * Reusable meeting templates. Every ability here is `meeting.manage_templates`,
 * including reading, because a template is configuration rather than content.
 * "Schedule" builds a real meeting (with its agenda) from a template.
 */
class MeetingTemplateResource extends Resource
{
    protected static ?string $model = MeetingTemplate::class;

    protected static ?string $slug = 'meeting-templates';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?int $navigationSort = 43;

    public static function canViewAny(): bool
    {
        return Gate::allows('meeting.manage_templates');
    }

    public static function canCreate(): bool
    {
        return Gate::allows('meeting.manage_templates');
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('meeting.manage_templates');
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('meeting.manage_templates');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('meeting_type_id')->label('Meeting type')
                ->options(fn (): array => MeetingType::query()->orderBy('name')->pluck('name', 'id')->all())
                ->required(),
            Textarea::make('description')->maxLength(2000)->columnSpanFull(),
            TextInput::make('default_duration')->label('Default duration (minutes)')->numeric()->integer()->minValue(1)->maxValue(1440),
            TextInput::make('default_location')->maxLength(255),
            Select::make('default_priority')
                ->options(collect(Priority::cases())->mapWithKeys(fn (Priority $p): array => [$p->value => $p->label()])->all())
                ->default(Priority::Normal->value)->required(),
            Toggle::make('is_active')->default(true),
            Repeater::make('agendaItems')
                ->label('Agenda')
                ->relationship()
                ->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    Textarea::make('description')->maxLength(2000)->rows(2),
                ])
                ->orderColumn('sort_order')
                ->addActionLabel('Add agenda item')
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('meetingType.name')->label('Type'),
            TextEntry::make('default_duration')->suffix(' min')->placeholder('-'),
            TextEntry::make('default_location')->placeholder('-'),
            TextEntry::make('default_priority')->badge()->formatStateUsing(fn ($state): string => ucfirst($state instanceof Priority ? $state->value : (string) $state)),
            TextEntry::make('is_active')->label('Active')->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
            TextEntry::make('description')->placeholder('-')->columnSpanFull(),
            RepeatableEntry::make('agendaItems')->label('Agenda')->schema([TextEntry::make('title')])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('meetingType:id,name')->withCount('agendaItems'))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('meetingType.name')->label('Type'),
                TextColumn::make('agenda_items_count')->label('Agenda items'),
                TextColumn::make('default_duration')->suffix(' min')->placeholder('-'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                static::scheduleAction(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Build a scheduled meeting from the template, carrying its agenda items over
     * with the template's default duration. An inactive template cannot be used.
     */
    public static function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->label('Schedule meeting')
            ->icon('heroicon-o-calendar-days')
            ->visible(fn (MeetingTemplate $record): bool => $record->is_active)
            ->authorize(fn (): bool => Gate::allows('meeting.manage_templates'))
            ->schema([
                TextInput::make('title')->required()->maxLength(255),
                DatePicker::make('meeting_date')->required(),
                TimePicker::make('start_time')->seconds(false)->required(),
                TimePicker::make('end_time')->seconds(false)->required()->after('start_time'),
                Select::make('organizer_id')->label('Organizer')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                Select::make('chairperson_id')->label('Chairperson')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                Select::make('department_id')->label('Department')
                    ->options(fn (): array => Department::query()->orderBy('department_name')->pluck('department_name', 'id')->all())->searchable(),
                Select::make('location_id')->label('Location')
                    ->options(fn (): array => Location::query()->orderBy('location_name')->pluck('location_name', 'id')->all())->searchable(),
            ])
            ->action(function (array $data, MeetingTemplate $record) {
                abort_unless(Gate::allows('meeting.manage_templates'), 403);

                if (! $record->is_active) {
                    Notification::make()->title('That template is inactive and cannot be scheduled.')->danger()->send();

                    return;
                }

                $user = auth()->user();

                $meeting = DB::transaction(function () use ($data, $record, $user): Meeting {
                    $meeting = Meeting::query()->create([
                        'title' => $data['title'],
                        'meeting_type_id' => $record->meeting_type_id,
                        'organizer_id' => $data['organizer_id'] ?? $user->id,
                        'chairperson_id' => $data['chairperson_id'] ?? $data['organizer_id'] ?? $user->id,
                        'department_id' => $data['department_id'] ?? $user->employee?->department_id,
                        'location' => $record->default_location,
                        'location_id' => $data['location_id'] ?? null,
                        'meeting_date' => $data['meeting_date'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'timezone' => config('app.timezone'),
                        'priority' => $record->default_priority,
                        'status' => 'scheduled',
                        'template_id' => $record->id,
                        'meeting_no' => app(MeetingNumberService::class)->generate(new Meeting(['meeting_date' => $data['meeting_date']])),
                        'created_by' => $user->id,
                    ]);

                    foreach ($record->agendaItems as $item) {
                        $meeting->agendas()->create([
                            'agenda_no' => $item->sort_order + 1,
                            'title' => $item->title,
                            'description' => $item->description,
                            'estimated_minutes' => $record->default_duration,
                            'status' => 'pending',
                            'sort_order' => $item->sort_order,
                        ]);
                    }

                    return $meeting;
                });

                app(ActivityLogger::class)->record(MeetingTemplate::class, $record, 'scheduled', null, ['meeting_id' => $meeting->id], $user->id);

                Notification::make()->title('Meeting scheduled from the template')->success()->send();

                return redirect(MeetingResource::getUrl('view', ['record' => $meeting]));
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingTemplates::route('/'),
            'create' => CreateMeetingTemplate::route('/create'),
            'view' => ViewMeetingTemplate::route('/{record}'),
            'edit' => EditMeetingTemplate::route('/{record}/edit'),
        ];
    }
}
