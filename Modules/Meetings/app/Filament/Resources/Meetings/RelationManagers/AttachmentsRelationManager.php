<?php

namespace Modules\Meetings\Filament\Resources\Meetings\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAttachment;
use Modules\Meetings\Services\MeetingAttachmentSynchroniser;

/**
 * Files attached to a meeting. Each upload is mirrored into the shared attachments
 * table by {@see MeetingAttachmentSynchroniser}, as the Blade form did.
 */
class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Attachments';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('file')->required()->disk('public')->directory('meeting-attachments')->maxSize(10240)->storeFileNamesIn('original_name'),
            Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')->searchable(),
                TextColumn::make('description')->limit(50)->placeholder('-'),
                TextColumn::make('file_size')->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1).' KB' : '-'),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Upload attachment')
                    ->using(function (array $data): Model {
                        /** @var Meeting $meeting */
                        $meeting = $this->getOwnerRecord();
                        abort_unless(Gate::allows('update', $meeting), 403);

                        $disk = Storage::disk('public');

                        $attachment = MeetingAttachment::create([
                            'meeting_id' => $meeting->id,
                            'file_path' => $data['file'],
                            'file_name' => $data['original_name'] ?? basename($data['file']),
                            'file_type' => $disk->mimeType($data['file']),
                            'file_size' => $disk->size($data['file']),
                            'description' => $data['description'] ?? null,
                            'uploaded_by' => auth()->id(),
                        ]);

                        app(MeetingAttachmentSynchroniser::class)->mirror($meeting, $attachment, 'public');

                        return $attachment;
                    }),
            ])
            ->recordActions([
                Action::make('download')->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (MeetingAttachment $record): string => asset('storage/'.$record->file_path), true),
                DeleteAction::make()->before(fn () => abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403)),
            ]);
    }
}
