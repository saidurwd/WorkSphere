<?php

namespace Modules\Obligations\Filament\Resources\Documents;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Obligations\Filament\Resources\Documents\Pages\ListObligationDocuments;
use Modules\Obligations\Filament\Resources\Obligations\ObligationResource;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationDocument;

/**
 * Every document across the obligations the viewer can see, read-only. Uploading
 * and deleting happen on the obligation itself.
 *
 * The Blade list showed every document in the system to every signed-in user; this
 * one is narrowed by the same visibility rule as the obligations themselves.
 */
class ObligationDocumentResource extends Resource
{
    protected static ?string $model = ObligationDocument::class;

    protected static ?string $modelLabel = 'obligation document';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Obligation documents';

    protected static ?int $navigationSort = 35;

    protected static ?string $slug = 'obligation-documents';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['obligation:id,obligation_no,title', 'uploader:id,name']);

        return $query->whereHas('obligation', fn (Builder $o): Builder => $o->visibleTo(auth()->user()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('obligation.obligation_no')->label('Obligation')->searchable(),
                TextColumn::make('obligation.title')->label('Title')->limit(40)->searchable(),
                TextColumn::make('document_type')->badge(),
                TextColumn::make('file_name')->searchable(),
                TextColumn::make('expiry_date')->date()->placeholder('-'),
                TextColumn::make('uploader.name')->label('Uploaded by')->placeholder('-')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('document_type')
                    ->options(fn (): array => ObligationDocument::query()
                        ->whereHas('obligation', fn (Builder $o): Builder => $o->visibleTo(auth()->user()))
                        ->distinct()->pluck('document_type', 'document_type')->all()),
            ])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (ObligationDocument $record): string => asset('storage/'.$record->file_path), true),
                Action::make('open')
                    ->label('Obligation')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ObligationDocument $record): string => ObligationResource::getUrl('view', ['record' => $record->obligation_id])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObligationDocuments::route('/'),
        ];
    }
}
