<?php

namespace App\Filament\Evaluator\Resources\Applications\Pages;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use App\Models\ApplicationStatusLog;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    /**
     * Create an evaluation record if one doesn't exist yet.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->evaluation()->firstOrCreate(
            [],
            [
                'evaluator_id' => Auth::id(),
            ]
        );

        return $data;
    }

    /**
     * Update evaluator information after saving.
     */
    protected function afterSave(): void
    {
        $evaluation = $this->record->evaluation;

        $evaluation->update([
            'evaluator_id' => auth()->id(),
            'evaluated_at' => now(),
        ]);

        $this->record->update([
            'status' => 'evaluated',
        ]);

        ApplicationStatusLog::create([
            'application_id' => $this->record->id,
            'status' => 'evaluated',
            'remarks' => $evaluation->remarks,
            'changed_by' => auth()->id(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
