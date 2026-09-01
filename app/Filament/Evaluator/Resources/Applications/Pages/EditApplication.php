<?php

namespace App\Filament\Evaluator\Resources\Applications\Pages;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\ApplicationControlNumber;
use App\Models\ApplicationEvaluation;
use App\Models\ApplicationStatusLog;
use App\Support\EvaluationChecklist;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;




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




    protected function saveControlNumber(): void
    {
        $controlNumber = trim((string) ($this->data['control_number_input'] ?? ''));

        if ($controlNumber === '') {
            return;
        }

        $existing = $this->record->controlNumber;

        if ($existing && $existing->control_number === $controlNumber) {
            return;
        }

        $inUse = ApplicationControlNumber::where('control_number', $controlNumber)
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
            ->exists();

        if ($inUse) {
            Notification::make()
                ->title('Control number already in use.')
                ->danger()
                ->send();

            return;
        }

        if ($existing) {
            $existing->update(['control_number' => $controlNumber]);
        } else {
            ApplicationControlNumber::create([
                'application_id' => $this->record->id,
                'control_number' => $controlNumber,
                'generated_by' => Auth::id(),
            ]);
        }

        $this->record->unsetRelation('controlNumber');
    }

    protected function afterSave(): void
    {
        $this->saveControlNumber();

        $evaluation = $this->record->evaluation;

        $result = EvaluationChecklist::computeResult(
            $evaluation->documentary_mandatory,
            $evaluation->qs_education_met,
            $evaluation->qs_experience_met,
            $evaluation->qs_training_met,
            $evaluation->qs_eligibility_met,
            currentlyExcluded: $evaluation->result === ApplicationEvaluation::RESULT_EXCLUDED,
        );

        $evaluation->update([
            'evaluator_id' => auth()->id(),
            'evaluated_at' => now(),
            'result' => $result,
            'recommended' => $result === ApplicationEvaluation::RESULT_QUALIFIED,
        ]);

        // Status tracks the QS outcome directly: Meet the QS on every category
        // marks the application Evaluated, an active Exclude Applicant marks
        // it Excluded, and anything else (still pending review, or a completed
        // review that didn't qualify) stays Pending. Never touch a terminal
        // status (approved/rejected).
        $newStatus = match ($result) {
            ApplicationEvaluation::RESULT_QUALIFIED => 'evaluated',
            ApplicationEvaluation::RESULT_EXCLUDED => 'excluded',
            default => 'pending',
        };

        if (
            $this->record->status !== $newStatus
            && in_array($this->record->status, ['pending', 'evaluated', 'excluded'], true)
        ) {
            $this->record->update([
                'status' => $newStatus,
            ]);

            ApplicationStatusLog::create([
                'application_id' => $this->record->id,
                'status' => $newStatus,
                'remarks' => $evaluation->remarks,
                'changed_by' => auth()->id(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->outlined()
                ->extraAttributes(['style' => 'box-shadow:inset 0 0 0 1px #1e3a8a;color:#1e3a8a;background-color:transparent;']),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler($this->getSubmitFormLivewireMethodName()),
            $this->getRelationManagersContentComponent(),
        ]);
    }
}
