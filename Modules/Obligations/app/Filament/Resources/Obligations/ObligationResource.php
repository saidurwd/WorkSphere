<?php

namespace Modules\Obligations\Filament\Resources\Obligations;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Policies\ObligationPolicy;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Obligations\Filament\Resources\Obligations\Pages\CreateObligation;
use Modules\Obligations\Filament\Resources\Obligations\Pages\EditObligation;
use Modules\Obligations\Filament\Resources\Obligations\Pages\ListObligations;
use Modules\Obligations\Filament\Resources\Obligations\Pages\ViewObligation;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationCategory;
use Modules\Obligations\Models\ObligationType;
use Modules\Obligations\Models\Vendor;

/**
 * Obligations in the Filament workspace panel.
 *
 * Abilities come from {@see ObligationPolicy}. The list is narrowed
 * as the Blade list was: without `obligation.view` you see obligations you own or
 * hold an active responsibility on. A super-admin sees them all.
 */
class ObligationResource extends Resource
{
    protected static ?string $model = Obligation::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 30;

    public const STATUSES = [
        'active' => 'Active',
        'upcoming' => 'Upcoming',
        'action_required' => 'Action required',
        'renewal_in_progress' => 'Renewal in progress',
        'pending_approval' => 'Pending approval',
        'purchase_in_progress' => 'Purchase in progress',
        'renewed' => 'Renewed',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
        'not_required' => 'Not required',
        'archived' => 'Archived',
    ];

    public const LEVELS = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['type:id,type_name', 'owner:id,name', 'vendor:id,vendor_name', 'department:id,department_name']);

        $user = auth()->user();

        if ($user instanceof User && ! $user->hasPermission('obligation.view') && ! $user->hasRole('super-admin')) {
            $query->where(function (Builder $inner) use ($user): void {
                $inner->where('owner_user_id', $user->id)
                    ->orWhereHas('responsibilities', fn (Builder $r): Builder => $r->where('user_id', $user->id)->where('active', true));
            });
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    protected static function users(): array
    {
        return User::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Obligation')->columns(2)->schema([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->columnSpanFull(),
                Select::make('obligation_type_id')->label('Type')
                    ->options(fn (): array => ObligationType::query()->where('active', true)->orderBy('type_name')->pluck('type_name', 'id')->all())
                    ->searchable()->required(),
                Select::make('category_id')->label('Category')
                    ->options(fn (): array => ObligationCategory::query()->where('active', true)->orderBy('category_name')->pluck('category_name', 'id')->all())
                    ->searchable()->required(),
                Select::make('status')->options(self::STATUSES)->default('active')->required(),
                Select::make('priority')->options(self::LEVELS)->default('medium')->required(),
                Select::make('risk_level')->options(self::LEVELS)->default('medium')->required(),
            ]),
            Section::make('Where')->columns(2)->schema([
                Select::make('company_id')->label('Company')
                    ->options(fn (): array => Company::query()->where('status', 'active')->orderBy('company_name')->pluck('company_name', 'id')->all())
                    ->searchable()->required(),
                Select::make('department_id')->label('Department')
                    ->options(fn (): array => Department::query()->where('status', 'active')->orderBy('department_name')->pluck('department_name', 'id')->all())
                    ->searchable()->required(),
                Select::make('location_id')->label('Location')
                    ->options(fn (): array => Location::query()->where('status', 'active')->orderBy('location_name')->pluck('location_name', 'id')->all())
                    ->searchable(),
                Select::make('vendor_id')->label('Vendor')
                    ->options(fn (): array => Vendor::query()->where('status', 'active')->orderBy('vendor_name')->pluck('vendor_name', 'id')->all())
                    ->searchable(),
            ]),
            Section::make('People')->columns(2)->schema([
                Select::make('owner_user_id')->label('Owner')->options(fn (): array => self::users())->searchable()->required(),
                Select::make('backup_user_id')->label('Backup')->options(fn (): array => self::users())->searchable(),
                Select::make('reviewer_user_id')->label('Reviewer')->options(fn (): array => self::users())->searchable(),
                Select::make('approver_user_id')->label('Approver')->options(fn (): array => self::users())->searchable(),
            ]),
            Section::make('Dates and renewal')->columns(2)->schema([
                DatePicker::make('start_date')->required(),
                DatePicker::make('expiry_date')->required()->afterOrEqual('start_date'),
                Toggle::make('renewal_required')->default(true),
                Toggle::make('auto_renew')->default(false),
                TextInput::make('recurrence_type')->maxLength(50),
                TextInput::make('recurrence_interval')->numeric()->integer()->minValue(1),
            ]),
            Section::make('Cost and notes')->columns(2)->schema([
                TextInput::make('estimated_cost')->numeric()->minValue(0),
                TextInput::make('currency')->default('BDT')->required()->maxLength(3),
                Textarea::make('notes')->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('obligation_no')->label('No.'),
            TextEntry::make('title'),
            TextEntry::make('status')->badge()->formatStateUsing(fn (string $state): string => self::STATUSES[$state] ?? $state),
            TextEntry::make('priority')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state)),
            TextEntry::make('risk_level')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state)),
            TextEntry::make('type.type_name')->label('Type')->placeholder('-'),
            TextEntry::make('category.category_name')->label('Category')->placeholder('-'),
            TextEntry::make('company.company_name')->label('Company')->placeholder('-'),
            TextEntry::make('department.department_name')->label('Department')->placeholder('-'),
            TextEntry::make('location.location_name')->label('Location')->placeholder('-'),
            TextEntry::make('vendor.vendor_name')->label('Vendor')->placeholder('-'),
            TextEntry::make('owner.name')->label('Owner')->placeholder('-'),
            TextEntry::make('backupUser.name')->label('Backup')->placeholder('-'),
            TextEntry::make('reviewer.name')->label('Reviewer')->placeholder('-'),
            TextEntry::make('approver.name')->label('Approver')->placeholder('-'),
            TextEntry::make('start_date')->date(),
            TextEntry::make('expiry_date')->date(),
            TextEntry::make('renewal_required')->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
            TextEntry::make('auto_renew')->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
            TextEntry::make('estimated_cost')->placeholder('-'),
            TextEntry::make('currency')->placeholder('-'),
            TextEntry::make('description')->placeholder('-')->columnSpanFull(),
            TextEntry::make('notes')->placeholder('-')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obligation_no')->label('No.')->searchable()->toggleable(),
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('type.type_name')->label('Type')->toggleable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => self::STATUSES[$state] ?? $state),
                TextColumn::make('priority')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('risk_level')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state))->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->toggleable(),
                TextColumn::make('vendor.vendor_name')->label('Vendor')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expiry_date')->date()->sortable(),
            ])
            ->defaultSort('expiry_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options(self::STATUSES),
                SelectFilter::make('priority')->options(self::LEVELS),
                SelectFilter::make('risk_level')->options(self::LEVELS),
                SelectFilter::make('obligation_type_id')->label('Type')->relationship('type', 'type_name'),
                SelectFilter::make('department_id')->label('Department')->relationship('department', 'department_name'),
                SelectFilter::make('company_id')->label('Company')->relationship('company', 'company_name'),
                SelectFilter::make('owner_user_id')->label('Owner')->relationship('owner', 'name')->searchable()->preload(),
                SelectFilter::make('vendor_id')->label('Vendor')->relationship('vendor', 'vendor_name'),
                Filter::make('expiry')
                    ->schema([
                        Select::make('period')->options([
                            '7_days' => 'Next 7 days',
                            '30_days' => 'Next 30 days',
                            '90_days' => 'Next 90 days',
                            'expired' => 'Already expired',
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $today = now()->startOfDay();

                        return match ($data['period'] ?? null) {
                            '7_days' => $query->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)]),
                            '30_days' => $query->whereBetween('expiry_date', [$today, $today->copy()->addDays(30)]),
                            '90_days' => $query->whereBetween('expiry_date', [$today, $today->copy()->addDays(90)]),
                            'expired' => $query->where('expiry_date', '<', $today)
                                ->whereNotIn('status', ['renewed', 'cancelled', 'not_required', 'archived']),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObligations::route('/'),
            'create' => CreateObligation::route('/create'),
            'view' => ViewObligation::route('/{record}'),
            'edit' => EditObligation::route('/{record}/edit'),
        ];
    }
}
