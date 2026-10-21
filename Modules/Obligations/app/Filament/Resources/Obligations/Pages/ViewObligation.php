<?php

namespace Modules\Obligations\Filament\Resources\Obligations\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Obligations\Filament\Resources\Obligations\ObligationResource;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationRenewal;
use Modules\Obligations\Models\Vendor;
use Modules\Obligations\Services\ObligationActivitySynchroniser;
use Modules\Obligations\Services\ObligationRecurrenceService;

class ViewObligation extends ViewRecord
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            $this->renewAction(),
        ];
    }

    /**
     * Renew: record the renewal, move the dates, reset the status and risk, and log
     * it — together, so a half-applied renewal cannot exist. Gated by `renew`.
     */
    protected function renewAction(): Action
    {
        return Action::make('renew')
            ->label('Renew')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->authorize('renew')
            ->fillForm(function (Obligation $record): array {
                $suggested = app(ObligationRecurrenceService::class)->suggestedRenewal($record);

                return [
                    'new_start_date' => $suggested['start'] ?? null,
                    'new_expiry_date' => $suggested['expiry'] ?? null,
                    'vendor_id' => $record->vendor_id,
                    'currency' => $record->currency,
                ];
            })
            ->schema(fn (Obligation $record): array => [
                DatePicker::make('new_start_date')->required()->afterOrEqual($record->start_date?->toDateString() ?? 'today'),
                DatePicker::make('new_expiry_date')->required()->after('new_start_date'),
                Select::make('vendor_id')->label('Vendor')
                    ->options(fn (): array => Vendor::query()->where('status', 'active')->orderBy('vendor_name')->pluck('vendor_name', 'id')->all())
                    ->searchable(),
                TextInput::make('cost')->numeric()->minValue(0),
                TextInput::make('currency')->maxLength(3),
                TextInput::make('purchase_reference')->maxLength(255),
                TextInput::make('invoice_reference')->maxLength(255),
                Textarea::make('remarks')->columnSpanFull(),
                FileUpload::make('document')->label('Renewal certificate')->disk('public')->directory('obligation-documents')->maxSize(10240)->storeFileNamesIn('document_name'),
            ])
            ->action(function (array $data, Obligation $record): void {
                abort_unless(auth()->user()->can('renew', $record), 403);

                DB::transaction(function () use ($data, $record): void {
                    ObligationRenewal::create([
                        'obligation_id' => $record->id,
                        'previous_expiry_date' => $record->expiry_date,
                        'new_start_date' => $data['new_start_date'],
                        'new_expiry_date' => $data['new_expiry_date'],
                        'renewal_date' => now()->toDateString(),
                        'vendor_id' => $data['vendor_id'] ?? null,
                        'cost' => $data['cost'] ?? null,
                        'currency' => $data['currency'] ?? $record->currency,
                        'purchase_reference' => $data['purchase_reference'] ?? null,
                        'invoice_reference' => $data['invoice_reference'] ?? null,
                        'remarks' => $data['remarks'] ?? null,
                        'renewed_by' => auth()->id(),
                    ]);

                    if (! empty($data['document'])) {
                        $disk = Storage::disk('public');

                        $record->documents()->create([
                            'document_type' => 'RENEWAL_CERTIFICATE',
                            'file_name' => $data['document_name'] ?? basename($data['document']),
                            'file_path' => $data['document'],
                            'mime_type' => $disk->mimeType($data['document']),
                            'file_size' => $disk->size($data['document']),
                            'uploaded_by' => auth()->id(),
                        ]);
                    }

                    $record->update([
                        'start_date' => $data['new_start_date'],
                        'expiry_date' => $data['new_expiry_date'],
                        'status' => 'active',
                        'risk_level' => 'low',
                        'vendor_id' => $data['vendor_id'] ?? $record->vendor_id,
                        'estimated_cost' => $data['cost'] ?? $record->estimated_cost,
                    ]);

                    app(ObligationActivitySynchroniser::class)->record(
                        $record,
                        'RENEWED',
                        null,
                        $data,
                        'Obligation renewed',
                        auth()->id(),
                        request()->ip(),
                        request()->userAgent(),
                    );
                });

                $this->refreshFormData(['start_date', 'expiry_date', 'status', 'risk_level']);

                Notification::make()->title('Obligation renewed')->success()->send();
            });
    }
}
