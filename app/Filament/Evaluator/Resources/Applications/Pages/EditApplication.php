<?php

namespace App\Filament\Evaluator\Resources\Applications\Pages;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
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




    protected function afterSave(): void
    {
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
            'evaluated_at' => $result === ApplicationEvaluation::RESULT_PENDING_DOCUMENT_REVIEW
                ? null
                : now(),
            'result' => $result,
            'recommended' => $result === ApplicationEvaluation::RESULT_QUALIFIED,
        ]);

        // Status tracks the QS outcome directly: Meet the QS on every category,
        // or at least one category marked Did not Meet the QS, both finish the
        // review and mark the application Evaluated (the Recommended flag
        // captures which outcome it was). An active Exclude Applicant marks it
        // Excluded, and anything still pending review stays Pending. Never
        // touch a terminal status (qualified/disqualified).
        $newStatus = match ($result) {
            ApplicationEvaluation::RESULT_QUALIFIED, ApplicationEvaluation::RESULT_NOT_QUALIFIED => 'evaluated',
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
        return [];
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
