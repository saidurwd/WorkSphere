<?php

namespace Modules\Meetings\Filament\Resources\Meetings;

use App\Models\User;
use App\Policies\MeetingPolicy;
use App\Support\ReferenceData;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Modules\Meetings\Filament\Resources\Meetings\Pages\CreateMeeting;
use Modules\Meetings\Filament\Resources\Meetings\Pages\EditMeeting;
use Modules\Meetings\Filament\Resources\Meetings\Pages\ListMeetings;
use Modules\Meetings\Filament\Resources\Meetings\Pages\ViewMeeting;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\ActionItemsRelationManager;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\AgendasRelationManager;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\AttachmentsRelationManager;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\DecisionsRelationManager;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\ParticipantsRelationManager;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingType;

/**
 * Meetings in the Filament workspace panel.
 *
 * Abilities come from {@see MeetingPolicy}. Without `meeting.view`
 * you see the meetings you organise or take part in; a super-admin sees them all.
 */
class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 40;

    public const STATUSES = [
        'scheduled' => 'Scheduled',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'postponed' => 'Postponed',
    ];

    public const PRIORITIES = ['normal' => 'Normal', 'important' => 'Important', 'urgent' => 'Urgent'];

    public const PARTICIPANT_TYPES = [
        'organizer' => 'Organizer', 'chairperson' => 'Chairperson', 'member' => 'Member',
        'guest' => 'Guest', 'presenter' => 'Presenter', 'observer' => 'Observer',
    ];

    public const ATTENDANCE = [
        'invited' => 'Invited', 'accepted' => 'Accepted', 'declined' => 'Declined',
        'present' => 'Present', 'absent' => 'Absent', 'apology' => 'Apology',
    ];

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['type:id,name', 'organizer:id,name', 'department:id,department_name']);

        $user = auth()->user();

        if ($user instanceof User) {
            $query->visibleTo($user);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            Select::make('meeting_type_id')->label('Type')
                ->options(fn (): array => MeetingType::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                ->required(),
            Select::make('priority')->options(self::PRIORITIES)->default('normal')->required(),
            Select::make('organizer_id')->label('Organizer')
                ->options(fn (): array => app(ReferenceData::class)->users()->pluck('name', 'id')->all())
                ->default(fn (): ?int => auth()->id())
                ->searchable()->required(),
            Select::make('chairperson_id')->label('Chairperson')
                ->options(fn (): array => app(ReferenceData::class)->users()->pluck('name', 'id')->all())
                ->searchable(),
            Select::make('department_id')->label('Department')
                ->options(fn (): array => app(ReferenceData::class)->departments()->pluck('department_name', 'id')->all())
                ->searchable(),
            TextInput::make('location')->maxLength(255),
            DatePicker::make('meeting_date')->required(),
            TimePicker::make('start_time')->seconds(false)->required()
                ->formatStateUsing(fn ($state): ?string => $state ? Carbon::parse($state)->format('H:i') : null),
            TimePicker::make('end_time')->seconds(false)->required()->after('start_time')
                ->formatStateUsing(fn ($state): ?string => $state ? Carbon::parse($state)->format('H:i') : null),
            TextInput::make('timezone')->default(fn (): string => (string) config('app.timezone'))->required()->maxLength(50),
            Textarea::make('description')->columnSpanFull(),
            Textarea::make('agenda')->columnSpanFull(),
            // Participants are chosen when the meeting is created; afterwards they
            // are managed from the Participants tab.
            Repeater::make('participants')
                ->schema([
                    Select::make('user_id')->label('Person')
                        ->options(fn (): array => app(ReferenceData::class)->users()->pluck('name', 'id')->all())
                        ->searchable()->required(),
                    Select::make('participant_type')->options(self::PARTICIPANT_TYPES)->default('member')->required(),
                    Select::make('attendance_status')->options(self::ATTENDANCE)->default('invited')->required(),
                ])
                ->columns(3)
                ->columnSpanFull()
                ->visibleOn('create'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('meeting_no')->label('No.'),
            TextEntry::make('title'),
            TextEntry::make('status')->badge()->formatStateUsing(fn (string $state): string => self::STATUSES[$state] ?? $state),
            TextEntry::make('priority')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state)),
            TextEntry::make('type.name')->label('Type')->placeholder('-'),
            TextEntry::make('organizer.name')->label('Organizer')->placeholder('-'),
            TextEntry::make('chairperson.name')->label('Chairperson')->placeholder('-'),
            TextEntry::make('department.department_name')->label('Department')->placeholder('-'),
            TextEntry::make('location')->placeholder('-'),
            TextEntry::make('meeting_date')->date(),
            TextEntry::make('start_time')->time('H:i'),
            TextEntry::make('end_time')->time('H:i'),
            TextEntry::make('minutes_status')->label('Minutes')->badge()->placeholder('-'),
            TextEntry::make('description')->placeholder('-')->columnSpanFull(),
            TextEntry::make('agenda')->placeholder('-')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meeting_no')->label('No.')->searchable()->toggleable(),
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('type.name')->label('Type')->toggleable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => self::STATUSES[$state] ?? $state),
                TextColumn::make('organizer.name')->label('Organizer')->toggleable(),
                TextColumn::make('department.department_name')->label('Department')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('meeting_date')->date()->sortable(),
                TextColumn::make('start_time')->time('H:i'),
            ])
            ->defaultSort('meeting_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options(self::STATUSES),
                SelectFilter::make('meeting_type_id')->label('Type')->relationship('type', 'name'),
                SelectFilter::make('department_id')->label('Department')->relationship('department', 'department_name'),
                Filter::make('date_range')
                    ->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('meeting_date', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('meeting_date', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Meeting $record): bool => in_array($record->status, ['completed', 'approved'], true)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AgendasRelationManager::class,
            ParticipantsRelationManager::class,
            DecisionsRelationManager::class,
            ActionItemsRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetings::route('/'),
            'create' => CreateMeeting::route('/create'),
            'view' => ViewMeeting::route('/{record}'),
            'edit' => EditMeeting::route('/{record}/edit'),
        ];
    }
}
