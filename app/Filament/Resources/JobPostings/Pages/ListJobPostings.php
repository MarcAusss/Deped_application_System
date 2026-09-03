<?php

namespace App\Filament\Resources\JobPostings\Pages;

use App\Filament\Resources\JobPositions\JobPositionResource;
use App\Filament\Resources\JobPostings\JobPostingResource;
use App\Models\JobPosition;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Set;

class ListJobPostings extends ListRecords
{
    protected static string $resource = JobPostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage')
                ->label('Manage')
                ->icon('heroicon-o-briefcase')
                ->modalHeading('Manage Job Position')
                ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge)
                ->modalSubmitActionLabel('Post')
                ->modalFooterActionsAlignment(\Filament\Support\Enums\Alignment::Right)
                ->form([
                    Select::make('job_position_id')
                        ->label('Select Position')
                        ->options(fn () => JobPosition::orderBy('title')->pluck('title', 'id'))
                        ->optionsLimit(1000)
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            $job = $state ? JobPosition::find($state) : null;

                            $set('title', $job?->title);
                            $set('abbreviation', $job?->abbreviation);
                            $set('slots', $job?->slots ?? 1);
                            $set('description', $job?->description);
                            $set('salary_grade', $job?->salary_grade);
                            $set('monthly_salary', $job?->monthly_salary);
                            $set('education_requirement', $job?->education_requirement);
                            $set('training_requirement', $job?->training_requirement);
                            $set('min_training_hours', $job?->min_training_hours);
                            $set('experience_requirement', $job?->experience_requirement);
                            $set('min_experience_years', $job?->min_experience_years);
                            $set('eligibility_requirement', $job?->eligibility_requirement);
                            $set('posted_at', $job?->posted_at?->toDateString());
                            $set('until', $job?->until?->toDateString());
                            $set('until_time', filled($job?->until_time)
                                ? \Carbon\Carbon::parse($job->until_time)->format('g:i A')
                                : null);
                            $set('attachment_paths', $job?->attachment_paths ?? []);
                            $set('csc_publication_path', $job?->csc_publication_path);
                        }),

                    ...JobPositionResource::formFields(),
                ])
                ->action(function (array $data) {
                    $job = JobPosition::find($data['job_position_id']);

                    if (! $job) {
                        return;
                    }

                    unset($data['job_position_id']);

                    // Posting always publishes the position to applicants and stamps a posted date if it doesn't have one yet.
                    $data['is_open'] = true;
                    $data['posted_at'] = $data['posted_at'] ?? now()->toDateString();

                    // A JP Number is only ever generated the first time a position is posted.
                    if (blank($job->jp_number)) {
                        $data['jp_number'] = JobPosition::generateJpNumber();
                    }

                    $job->update($data);

                    Notification::make()
                        ->title('Job position posted')
                        ->body('It is now visible to applicants on the job listing.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
