<?php

namespace Modules\Obligations\Filament\Resources\Obligations\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationDocument;
use Modules\Obligations\Services\ObligationActivitySynchroniser;

/**
 * Files attached to an obligation. Uploading and deleting need `manageDocuments`
 * (the owner, or `obligation.manage_documents`) and are written to the
 * obligation's activity trail.
 */
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('manageDocuments', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('document_type')->required()->maxLength(255),
            FileUpload::make('file')
                ->required()
                ->disk('public')
                ->directory('obligation-documents')
                ->maxSize(10240)
                ->storeFileNamesIn('original_name'),
            DatePicker::make('document_date'),
            DatePicker::make('expiry_date'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('uploader:id,name'))
            ->columns([
                TextColumn::make('document_type')->badge(),
                TextColumn::make('file_name')->searchable(),
                TextColumn::make('file_size')->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1).' KB' : '-'),
                TextColumn::make('document_date')->date()->placeholder('-'),
                TextColumn::make('expiry_date')->date()->placeholder('-'),
                TextColumn::make('uploader.name')->label('Uploaded by')->placeholder('-')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Upload document')
                    ->using(function (array $data): Model {
                        /** @var Obligation $obligation */
                        $obligation = $this->getOwnerRecord();
                        abort_unless(Gate::allows('manageDocuments', $obligation), 403);

                        $path = $data['file'];
                        $disk = Storage::disk('public');

                        $document = $obligation->documents()->create([
                            'document_type' => $data['document_type'],
                            'file_name' => $data['original_name'] ?? basename($path),
                            'file_path' => $path,
                            'file_size' => $disk->size($path),
                            'mime_type' => $disk->mimeType($path),
                            'document_date' => $data['document_date'] ?? null,
                            'expiry_date' => $data['expiry_date'] ?? null,
                            'uploaded_by' => auth()->id(),
                        ]);

                        $this->record($obligation, 'DOCUMENT_UPLOADED', null, $document->toArray(), 'Document uploaded: '.$data['document_type']);

                        return $document;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (ObligationDocument $record): string => asset('storage/'.$record->file_path), true),
                DeleteAction::make()
                    ->before(fn () => abort_unless(Gate::allows('manageDocuments', $this->getOwnerRecord()), 403))
                    ->after(fn (ObligationDocument $record) => $this->record(
                        $this->getOwnerRecord(),
                        'DOCUMENT_DELETED',
                        $record->toArray(),
                        null,
                        'Document deleted: '.$record->file_name,
                    )),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    protected function record(Obligation $obligation, string $action, ?array $old, ?array $new, string $remarks): void
    {
        app(ObligationActivitySynchroniser::class)->record(
            $obligation,
            $action,
            $old,
            $new,
            $remarks,
            auth()->id(),
            request()->ip(),
            request()->userAgent(),
        );
    }
}
