<?php

namespace App\Filament\Evaluator\Resources\Applications\Pages;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use App\Filament\Evaluator\Resources\Evaluated\EvaluatedResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\ApplicationEvaluation;
use App\Models\ApplicationStatusLog;
use App\Support\EvaluationChecklist;
use Filament\Actions\Action;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    // Filament pages are Livewire components: every button click (Save
    // Changes, Exclude Applicant) is an AJAX call to Livewire's own update
    // endpoint, not a request to this page's URL — so request()->query()
    // is empty by the time those actions run. Capturing "from" once here,
    // on the real page load, keeps it available across those AJAX calls
    // via Livewire's normal public-property persistence.
    public ?string $from = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->from = request()->query('from');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Evaluation only opens once the position's application period has
        // actually closed — but that only gates STARTING a fresh evaluation
        // on a still-pending application. An application that's already been
        // evaluated or excluded must stay editable regardless of the
        // position's current deadline (e.g. it was later reopened/extended),
        // otherwise the "Edit" button on Evaluated Status locks itself out.
        if ($this->record->status === 'pending' && ! $this->record->jobPosition?->hasDeadlinePassed()) {
            abort(403, 'This position is still accepting applications — evaluation opens once the application period closes.');
        }

        $evaluation = $this->record->evaluation;

        // Opening this page claims it immediately — another evaluator
        // trying to open the same application (button or direct URL) is
        // blocked from this point on, even before anything is saved.
        if ($evaluation && $evaluation->evaluator_id && $evaluation->evaluator_id !== Auth::id()) {
            abort(403, 'This application is already being evaluated by another evaluator.');
        }

        $evaluation = $this->record->evaluation()->firstOrCreate(
            [],
            ['evaluator_id' => Auth::id()],
        );

        // firstOrCreate() above inserts via a fresh query, which does not
        // refresh the "evaluation" relation already cached on $this->record
        // from the read at the top of this method — without this, a
        // brand-new application (no prior evaluation row) still has that
        // stale null cached, and the form's relationship-mapped Evaluation
        // Result section crashes reading properties off it.
        $this->record->setRelation('evaluation', $evaluation);

        return $data;
    }

    /**
     * True once this evaluator has actually saved something — the
     * evaluation row's updated_at only moves past created_at when an
     * update() has happened, which only occurs via Save Changes/Exclude.
     * Merely opening the page (which creates the row to claim it) does not.
     */
    protected function hasUnsavedClaimOnly(): bool
    {
        $evaluation = $this->record->evaluation;

        return $evaluation
            && $evaluation->evaluator_id === Auth::id()
            && $evaluation->created_at->equalTo($evaluation->updated_at);
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
        return [
            Action::make('backToList')
                ->label('Back to List')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->action(function () {
                    // Leaving without ever saving releases the claim, so
                    // this application is free again for any evaluator —
                    // including this one — to pick up later.
                    if ($this->hasUnsavedClaimOnly()) {
                        $this->record->evaluation()->delete();
                    }

                    $this->redirect($this->getRedirectUrl());
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->from === 'evaluated') {
            return EvaluatedResource::getUrl('index');
        }

        return $this->getResource()::getUrl('index');
    }

    public function getResourceBreadcrumbs(): array
    {
        if ($this->from === 'evaluated') {
            return [
                EvaluatedResource::getUrl('index') => EvaluatedResource::getBreadcrumb(),
            ];
        }

        return parent::getResourceBreadcrumbs();
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
