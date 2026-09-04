<?php

namespace App\Filament\Resources\Applications;

use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Filament\Resources\Applications\Pages;
use App\Filament\Resources\Applications\RelationManagers;
use App\Support\EvaluationChecklist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Applications';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereNotIn('status', ['disqualified', 'qualified']);
    }

    public static function getRecordRouteBindingEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Disqualified/qualified applications are excluded from the list query above
        // (they live in the Archive/Approvals pages instead), but the View page
        // still needs to resolve them — e.g. from those pages' "View" link.
        return parent::getEloquentQuery();
    }

    




    public static function form(Schema $schema): Schema
    {
        $renderTable = function (array $headers, array $rows): \Illuminate\Support\HtmlString {
            if (empty($rows)) {
                return new \Illuminate\Support\HtmlString(
                    '<p style="color:#64748b;font-size:0.875rem;">No records submitted.</p>'
                );
            }

            $html = '<div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;font-size:0.875rem;">';

            $html .= '<thead><tr>';
            foreach ($headers as $header) {
                $html .= '<th style="text-align:left;padding:0.5rem 0.75rem;border-bottom:2px solid #cbd5e1;font-weight:700;">' . e($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td style="padding:0.5rem 0.75rem;border-bottom:1px solid #e2e8f0;">' . $cell . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';

            return new \Illuminate\Support\HtmlString($html);
        };

        return $schema->components([

            Section::make('Application Info')
                ->icon('heroicon-o-briefcase')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema([
                    Select::make('job_position_id')
                        ->label('Job Position')
                        ->relationship('jobPosition', 'title')
                        ->disabled(),

                    TextInput::make('status')
                        ->label('Current Status')
                        ->disabled(),
                ])
                ->columns(2),

            Section::make('Control Number')
                ->icon('heroicon-o-hashtag')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema([
                    Placeholder::make('control_number')
                        ->label('Assigned Control Number')
                        ->content(fn ($record) => $record?->controlNumber?->control_number ?? 'Not yet assigned'),

                    Placeholder::make('assigned_by')
                        ->label('Assigned By')
                        ->content(fn ($record) => $record?->controlNumber?->user?->name ?? '—'),
                ])
                ->columns(2),

            Section::make('Documentary Requirements')
                ->icon('heroicon-o-clipboard-document-check')
                ->description('Submitted by the evaluator. View only — admin cannot edit this.')
                ->extraAttributes(['class' => 'evaluation-checklist dark-blue-header'])
                ->visible(fn ($record) => $record?->evaluation !== null)
                ->schema([
                    Section::make(fn ($record) => 'Mandatory Requirements (' . EvaluationChecklist::countSelected(
                        EvaluationChecklist::mandatoryRequirements(),
                        $record?->evaluation?->documentary_mandatory
                    ) . ' of ' . EvaluationChecklist::mandatoryCount() . ')')
                        ->schema([
                            CheckboxList::make('documentary_mandatory_view')
                                ->hiddenLabel()
                                ->options(EvaluationChecklist::mandatoryRequirements())
                                ->columns(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(fn ($component, $record) => $component->state($record?->evaluation?->documentary_mandatory ?? [])),
                        ]),

                    Section::make(fn ($record) => 'Other Requirements (' . EvaluationChecklist::countSelected(
                        EvaluationChecklist::otherRequirements(),
                        $record?->evaluation?->documentary_other
                    ) . ' of ' . EvaluationChecklist::otherCount() . ')')
                        ->schema([
                            CheckboxList::make('documentary_other_view')
                                ->hiddenLabel()
                                ->options(EvaluationChecklist::otherRequirements())
                                ->columns(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(fn ($component, $record) => $component->state($record?->evaluation?->documentary_other ?? [])),
                        ]),
                ]),

            Section::make('Qualification Standards')
                ->icon('heroicon-o-scale')
                ->description("Review the applicant's details against each qualification standard. View only — admin cannot edit this.")
                ->extraAttributes(['class' => 'evaluation-checklist dark-blue-header'])
                ->visible(fn ($record) => $record?->evaluation !== null
                    && EvaluationChecklist::isDocumentaryComplete($record->evaluation->documentary_mandatory))
                ->schema(function () {
                    $box = fn (string $title, string $value): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                        '<div style="display:block;width:100%;height:100%;min-height:6rem;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.5rem 0.75rem;">'
                        . '<p style="font-size:1rem;font-weight:700;margin-bottom:0.25rem;">' . e($title) . '</p>'
                        . '<span>' . e($value) . '</span>'
                        . '</div>'
                    );

                    $metBadge = function (?bool $met): \Illuminate\Support\HtmlString {
                        $style = match ($met) {
                            true => 'background:#15803d;color:#ffffff',
                            false => 'background:#b91c1c;color:#ffffff',
                            default => 'background:#f1f5f9;color:#475569',
                        };
                        $label = match ($met) {
                            true => 'Meet the QS',
                            false => 'Did not Meet the QS',
                            default => 'Not yet marked',
                        };

                        return new \Illuminate\Support\HtmlString(
                            '<span style="' . $style . ';display:inline-block;padding:.375rem .75rem;border-radius:.5rem;font-weight:700;font-size:.8rem;">'
                            . e($label)
                            . '</span>'
                        );
                    };

                    return [
                        Grid::make(2)->schema([
                            Section::make("Bachelor's Degree")
                                ->extraAttributes(['class' => 'qs-box-row'])
                                ->schema([
                                    Placeholder::make('qs_education_applicant')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $box('Applicant', EvaluationChecklist::applicantBachelorsDegree($record->application))),

                                    Placeholder::make('qs_education_standard')
                                        ->hiddenLabel()
                                        ->content(function ($record) {
                                            $value = $record->application->jobPosition->education_requirement
                                                ?: "Bachelor's Degree in Guidance Counseling or Psychology; or any Bachelor's Degree with atleast eighteen (18) units of courses in Guidance and Psychology; or Any  Bachelor's Degree with a minimum of eighteen (18) units of Behavioral Science courses that shall include 200 hours of supervised practicum or internship experience on guidance and counseling, preferably in a school or community setting";

                                            return new \Illuminate\Support\HtmlString(
                                                '<div style="display:block;width:100%;height:100%;min-height:6rem;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.5rem 0.75rem;">'
                                                . '<p style="font-size:1rem;font-weight:700;margin-bottom:0.25rem;">Qualification Standard</p>'
                                                . '<p style="font-size:0.75rem;line-height:1.4;">' . e($value) . '</p>'
                                                . '</div>'
                                            );
                                        }),

                                    Placeholder::make('qs_education_met_badge')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $metBadge($record->qs_education_met)),
                                ])
                                ->columns(2),

                            Section::make('Years of Experience')
                                ->extraAttributes(['class' => 'qs-box-row'])
                                ->schema([
                                    Placeholder::make('qs_experience_applicant')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $box('Applicant', EvaluationChecklist::applicantYearsOfExperience($record->application))),

                                    Placeholder::make('qs_experience_standard')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $box('Qualification Standard', ($record->application->jobPosition->min_experience_years ?? 0) . ' minimum year(s)')),

                                    Placeholder::make('qs_experience_met_badge')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $metBadge($record->qs_experience_met)),
                                ])
                                ->columns(2),

                            Section::make('Hours of Training')
                                ->extraAttributes(['class' => 'qs-box-row'])
                                ->schema([
                                    Placeholder::make('qs_training_applicant')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $box('Applicant', EvaluationChecklist::applicantHoursOfTraining($record->application))),

                                    Placeholder::make('qs_training_standard')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $box('Qualification Standard', ($record->application->jobPosition->min_training_hours ?? 0) . ' minimum hour(s)')),

                                    Placeholder::make('qs_training_met_badge')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $metBadge($record->qs_training_met)),
                                ])
                                ->columns(2),

                            Section::make('Eligibility')
                                ->extraAttributes(['class' => 'qs-box-row'])
                                ->schema([
                                    Placeholder::make('qs_eligibility_applicant')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $box('Applicant', EvaluationChecklist::applicantEligibility($record->application))),

                                    Placeholder::make('qs_eligibility_standard')
                                        ->hiddenLabel()
                                        ->content(function ($record) {
                                            $value = $record->application->jobPosition->eligibility_requirement
                                                ?: 'Career Service Professional, Second Level Eligibility and RA 1080.';

                                            return new \Illuminate\Support\HtmlString(
                                                '<div style="display:block;width:100%;height:100%;min-height:6rem;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.5rem 0.75rem;">'
                                                . '<p style="font-size:1rem;font-weight:700;margin-bottom:0.25rem;">Qualification Standard</p>'
                                                . '<p style="font-size:0.75rem;line-height:1.4;">' . e($value) . '</p>'
                                                . '</div>'
                                            );
                                        }),

                                    Placeholder::make('qs_eligibility_met_badge')
                                        ->hiddenLabel()
                                        ->content(fn ($record) => $metBadge($record->qs_eligibility_met)),
                                ])
                                ->columns(2),
                        ]),
                    ];
                })
                ->relationship('evaluation'),

            Section::make('Applicant Profile')
                ->icon('heroicon-o-user')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema(function () {
                    $box = fn (string $title, string $value): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                        '<div style="display:block;width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.5rem 0.75rem;">'
                        . '<p style="font-size:0.875rem;font-weight:700;margin-bottom:0.25rem;">' . e($title) . '</p>'
                        . '<span>' . e($value) . '</span>'
                        . '</div>'
                    );

                    return [
                        Placeholder::make('full_name')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Full Name', $record?->profile?->full_name ?? '—')),

                        Placeholder::make('email')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Email Address', $record?->profile?->email ?? '—')),

                        Placeholder::make('phone')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Phone Number', $record?->profile?->phone ?? '—')),

                        Placeholder::make('address')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Address', $record?->profile?->address ?? '—')),

                        Placeholder::make('birth_date')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Birth Date', $record?->profile?->birth_date?->format('M d, Y') ?? '—')),

                        Placeholder::make('sex')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Sex', $record?->profile?->sex ?? '—')),

                        Placeholder::make('civil_status')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Civil Status', $record?->profile?->civil_status ?? '—')),

                        Placeholder::make('religion')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Religion', $record?->profile?->religion ?? '—')),

                        Placeholder::make('disability')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Disability (if any)', $record?->profile?->disability ?? '—')),

                        Placeholder::make('ethnic_group')
                            ->hiddenLabel()
                            ->content(fn ($record) => $box('Ethnic Group', $record?->profile?->ethnic_group ?? '—')),
                    ];
                })
                ->columns(2),

            Section::make('Evaluation Result')
                ->icon('heroicon-o-flag')
                ->description('Completed by the evaluator. View only — admin cannot edit this.')
                ->extraAttributes(['class' => 'evaluation-checklist dark-blue-header'])
                ->visible(fn ($record) => $record?->evaluation !== null)
                ->schema([
                    Placeholder::make('result_notice')
                        ->hiddenLabel()
                        ->content(new \Illuminate\Support\HtmlString(
                            '<p style="font-size:0.8rem;color:#64748b;margin-bottom:0.5rem;">'
                            . e('The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are mark Excluded only when specifically using the Exclude button below.')
                            . '</p>'
                        ))
                        ->columnSpanFull(),

                    Placeholder::make('result_badge')
                        ->hiddenLabel()
                        ->content(function ($record) {
                            $evaluation = $record?->evaluation;
                            $result = $evaluation?->result ?? ApplicationEvaluation::RESULT_PENDING_DOCUMENT_REVIEW;

                            $disqualifiedCategories = EvaluationChecklist::disqualifiedCategories(
                                $evaluation?->qs_education_met,
                                $evaluation?->qs_experience_met,
                                $evaluation?->qs_training_met,
                                $evaluation?->qs_eligibility_met,
                            );

                            $colors = [
                                'success' => ['background:#dcfce7', 'color:#15803d', 'border-color:#15803d'],
                                'danger' => ['background:#fee2e2', 'color:#b91c1c', 'border-color:#b91c1c'],
                                'gray' => ['background:#f1f5f9', 'color:#475569', 'border-color:#94a3b8'],
                            ];
                            $style = implode(';', $colors[EvaluationChecklist::resultColor($result)]);

                            $badgeColors = [
                                'success' => 'background:#15803d;color:#ffffff',
                                'danger' => 'background:#b91c1c;color:#ffffff',
                                'gray' => 'background:#475569;color:#ffffff',
                            ];
                            $badgeStyle = $badgeColors[EvaluationChecklist::resultColor($result)];

                            return new \Illuminate\Support\HtmlString(
                                '<div style="display:block;width:100%;box-sizing:border-box;border:2px solid;' . $style . ';border-radius:.5rem;padding:.5rem 1rem;">'
                                . '<span style="' . $badgeStyle . ';display:inline-block;padding:.25rem .625rem;border-radius:.375rem;font-weight:700;font-size:.875rem;">'
                                . e(strtoupper(EvaluationChecklist::resultLabel($result)))
                                . '</span>'
                                . '<span style="margin-left:.5rem;font-size:.8rem;font-weight:700;color:#1e3a8a;">'
                                . e(EvaluationChecklist::resultDescription($result, $disqualifiedCategories))
                                . '</span>'
                                . '</div>'
                            );
                        })
                        ->columnSpanFull(),

                    Placeholder::make('evaluated_by')
                        ->label('Evaluated By')
                        ->content(fn ($record) => $record?->evaluation?->evaluator?->name ?? '—'),

                    Placeholder::make('evaluated_at')
                        ->label('Evaluated On')
                        ->content(fn ($record) => $record?->evaluation?->evaluated_at?->format('M d, Y h:i A') ?? '—'),

                    Textarea::make('remarks_view')
                        ->label('Remarks / Notes')
                        ->rows(4)
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn ($component, $record) => $component->state($record?->evaluation?->remarks))
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Evaluation Pending')
                ->icon('heroicon-o-clock')
                ->schema([
                    Placeholder::make('no_evaluation')
                        ->label('')
                        ->content('This application has not been evaluated yet. Qualify/Disqualify will become available once the evaluator submits their checklist.'),
                ])
                ->visible(fn ($record) => $record?->evaluation === null),

            Section::make('Educational Background')
                ->icon('heroicon-o-academic-cap')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema([
                    Placeholder::make('educations_table')
                        ->hiddenLabel()
                        ->content(fn ($record) => $renderTable(
                            ['Level', 'School / Institution', 'Degree / Course', 'Year Graduated'],
                            $record->educations->map(fn ($education) => [
                                e($education->level === "Other's" && filled($education->level_specify)
                                    ? "{$education->level} - {$education->level_specify}"
                                    : $education->level),
                                e($education->school ?? '—'),
                                e($education->degree ?? '—'),
                                e($education->year_graduated ?? '—'),
                            ])->all()
                        )),
                ]),

            Section::make('Work Experience')
                ->icon('heroicon-o-briefcase')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema([
                    Placeholder::make('experiences_table')
                        ->hiddenLabel()
                        ->content(fn ($record) => $renderTable(
                            ['Job Title', 'Company', 'First Day of Service', 'Last Day of Service', 'Responsibilities or Details'],
                            $record->experiences->map(fn ($experience) => [
                                e($experience->title ?? '—'),
                                e($experience->company ?? '—'),
                                e($experience->first_day ?? 'Not provided'),
                                e($experience->last_day ?? 'Not provided'),
                                e($experience->details ?? 'Not provided'),
                            ])->all()
                        )),
                ]),

            Section::make('Trainings & Seminars')
                ->icon('heroicon-o-presentation-chart-line')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema([
                    Placeholder::make('trainings_table')
                        ->hiddenLabel()
                        ->content(fn ($record) => $renderTable(
                            ['Training Title', 'Hours', 'Start', 'End'],
                            $record->trainings->map(fn ($training) => [
                                e($training->title ?? '—'),
                                e(($training->hours ?? '0') . ' hrs'),
                                e($training->training_date?->format('F Y') ?? 'Not provided'),
                                e($training->training_end_date?->format('F Y') ?? 'Not provided'),
                            ])->all()
                        )),
                ]),

            Section::make('Eligibilities / Licenses')
                ->icon('heroicon-o-identification')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema([
                    Placeholder::make('eligibilities_table')
                        ->hiddenLabel()
                        ->content(fn ($record) => $renderTable(
                            ['License / Eligibility', 'Rating', 'Date Issued', 'Valid Until'],
                            $record->eligibilities->map(fn ($eligibility) => [
                                e(in_array($eligibility->license_name, ['RA1080', "Other's"]) && filled($eligibility->license_specify)
                                    ? "{$eligibility->license_name} - {$eligibility->license_specify}"
                                    : $eligibility->license_name),
                                e($eligibility->rating ?? '—'),
                                e($eligibility->date_issued ? \Carbon\Carbon::parse($eligibility->date_issued)->format('M d, Y') : '—'),
                                e($eligibility->never_expires
                                    ? 'Never Expires'
                                    : ($eligibility->valid_until ? \Carbon\Carbon::parse($eligibility->valid_until)->format('M d, Y') : '—')),
                            ])->all()
                        )),
                ]),

            Section::make('Submitted Documents')
                ->icon('heroicon-o-paper-clip')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema([
                    Placeholder::make('documents_table')
                        ->hiddenLabel()
                        ->content(fn ($record) => $renderTable(
                            ['Document Type', 'File'],
                            $record->documents->map(fn ($document) => [
                                '<span style="background:#eef2ff;color:#1e3a8a;padding:.125rem .625rem;border-radius:9999px;font-weight:600;font-size:.75rem;">' . e($document->type) . '</span>',
                                '<a href="' . e(route('public-file', $document->file_path)) . '" target="_blank" rel="noopener" style="color:#1e3a8a;text-decoration:underline;">' . e(basename($document->file_path)) . '</a>',
                            ])->all()
                        )),
                ]),
        ]);
    }

    




    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('controlNumber.control_number')
                    ->label('Control #')
                    ->placeholder('Not assigned')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Applicant Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('profile.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('jobPosition.title')
                    ->label('Position Applied')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'      => 'gray',
                        'evaluated'    => 'warning',
                        'excluded'     => 'danger',
                        'qualified'    => 'success',
                        'disqualified' => 'danger',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])

            ->defaultSort('created_at', 'desc')

            ->recordActionsColumnLabel('Actions')
            ->recordActionsAlignment(\Filament\Support\Enums\Alignment::Center->value)

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'pending'   => 'Pending',
                        'evaluated' => 'Evaluated',
                        'excluded'  => 'Excluded',
                    ]),

                Tables\Filters\SelectFilter::make('job_position_id')
                    ->label('Filter by Position')
                    ->relationship('jobPosition', 'title'),
            ])

->actions([
    Action::make('view')
        ->label('View')
        ->icon('heroicon-o-eye')
        ->modalHeading(fn ($record) => $record?->profile?->full_name ?? 'Application Details')
        ->modalWidth(Width::FiveExtraLarge)
        ->modalSubmitAction(false)
        ->modalCancelActionLabel('Close')
        ->modalAutofocus(false)
        ->fillForm(fn ($record) => $record->attributesToArray())
        ->schema(fn (Schema $schema) => static::form($schema)),

    Action::make('qualify')
        ->label('Qualified')
        ->icon('heroicon-o-check-circle')
        ->color(fn ($record) => in_array($record->status, ['evaluated', 'excluded'], true) ? 'success' : 'gray')
        ->disabled(fn ($record) => ! in_array($record->status, ['evaluated', 'excluded'], true))
        ->tooltip(fn ($record) => ! in_array($record->status, ['evaluated', 'excluded'], true) ? 'Only evaluated or excluded applications can be marked Qualified.' : null)
        ->requiresConfirmation()
        ->modalHeading('Mark Application Qualified')
        ->modalDescription('Are you sure you want to mark this application Qualified? This finalizes the hiring decision.')
        ->modalSubmitActionLabel('Yes, mark Qualified')
        ->action(function ($record) {
            $record->update(['status' => 'qualified']);

            $record->logs()->create([
                'status' => 'qualified',
                'changed_by' => auth()->id(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Application marked Qualified')
                ->success()
                ->send();
        }),

            Action::make('disqualify')
                ->label('Disqualified')
                ->icon('heroicon-o-x-circle')
                ->color(fn ($record) => in_array($record->status, ['evaluated', 'excluded'], true) ? 'danger' : 'gray')
                ->disabled(fn ($record) => ! in_array($record->status, ['evaluated', 'excluded'], true))
                ->tooltip(fn ($record) => ! in_array($record->status, ['evaluated', 'excluded'], true) ? 'Only evaluated or excluded applications can be marked Disqualified.' : null)
                ->requiresConfirmation()
                ->modalHeading('Mark Application Disqualified')
                ->modalDescription('Are you sure you want to mark this application Disqualified? This application will be moved to archive.')
                ->modalSubmitActionLabel('Yes, mark Disqualified')
                ->action(function ($record) {
                    $record->update(['status' => 'disqualified']);

                    $record->logs()->create([
                        'status' => 'disqualified',
                        'changed_by' => auth()->id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Application marked Disqualified')
                        ->danger()
                        ->send();
                }),
        ])

            ->bulkActions([]);
    }

    




    public static function getRelations(): array
    {
        return [
            RelationManagers\EducationRelationManager::class,
            RelationManagers\ExperienceRelationManager::class,
            RelationManagers\TrainingRelationManager::class,
            RelationManagers\EligibilityRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    




    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'view'  => Pages\ViewApplication::route('/{record}'),
        ];
    }
}
