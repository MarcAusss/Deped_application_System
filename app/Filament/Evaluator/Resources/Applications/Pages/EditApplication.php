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

        // Without a control number assigned, the application isn't officially
        // under evaluation yet — keep it pending instead of marking it evaluated.
        if (blank($this->record->controlNumber)) {
            return;
        }

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
