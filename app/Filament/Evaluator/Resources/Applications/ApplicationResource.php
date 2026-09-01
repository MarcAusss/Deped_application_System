<?php

namespace App\Filament\Evaluator\Resources\Applications;

use App\Filament\Resources\Applications\ApplicationResource as ApplicationDetailsResource;
use App\Models\Application;
use App\Models\ApplicationControlNumber;
use App\Models\ApplicationEvaluation;
use App\Models\ApplicationStatusLog;
use App\Exports\ApplicationsExport;
use App\Support\EvaluationChecklist;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Evaluator\Resources\Applications\Pages;
use App\Support\IerApplicationFormatter;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use App\Filament\Evaluator\Resources\Applications\RelationManagers;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?string $slug = 'applications';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Applications';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereNotIn('status', ['rejected', 'approved']);
    }

    public static function getRecordRouteBindingEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Rejected/approved applications are excluded from the list query above
        // (they live in the Archive/Approvals pages instead), but the View page
        // still needs to resolve them — e.g. from those pages' "View" link.
        return parent::getEloquentQuery();
    }

    




    public static function form(Schema $schema): Schema
    {
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
                ->description('Assign a control number to this application before marking it as evaluated.')
                ->extraAttributes(['class' => 'dark-blue-header'])
                ->schema([
                    TextInput::make('control_number_input')
                        ->label('Assigned Control Number')
                        ->maxLength(50)
                        ->placeholder('e.g. DEP-2025-0001')
                        ->afterStateHydrated(fn ($component, $record) => $component->state($record?->controlNumber?->control_number)),

                    Placeholder::make('assigned_by')
                        ->label('Assigned By')
                        ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                            '<div style="display:block;width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.5rem 0.75rem;">'
                            . e($record?->controlNumber?->user?->name ?? '—')
                            . '</div>'
                        )),
                ])
                ->columns(2),

            Section::make('Documentary Requirements')
                ->relationship('evaluation')
                ->icon('heroicon-o-clipboard-document-check')
                ->description('Click submitted requirements. The mandatory requirements unlock the QS Evaluation automatically once completed.')
                ->extraAttributes(['class' => 'evaluation-checklist dark-blue-header'])
                ->schema([
                    Section::make(fn (Get $get) => 'Mandatory Requirements (' . EvaluationChecklist::countSelected(
                        EvaluationChecklist::mandatoryRequirements(),
                        $get('documentary_mandatory')
                    ) . ' of ' . EvaluationChecklist::mandatoryCount() . ')')
                        ->schema([
                            CheckboxList::make('documentary_mandatory')
                                ->hiddenLabel()
                                ->options(EvaluationChecklist::mandatoryRequirements())
                                ->columns(2)
                                ->live(),
                        ]),

                    Section::make(fn (Get $get) => 'Other Requirements (' . EvaluationChecklist::countSelected(
                        EvaluationChecklist::otherRequirements(),
                        $get('documentary_other')
                    ) . ' of ' . EvaluationChecklist::otherCount() . ')')
                        ->schema([
                            CheckboxList::make('documentary_other')
                                ->hiddenLabel()
                                ->options(EvaluationChecklist::otherRequirements())
                                ->columns(2)
                                ->live(),
                        ]),

                    Placeholder::make('documentary_summary')
                        ->hiddenLabel()
                        ->content(function (Get $get) {
                            $mandatoryDone = EvaluationChecklist::countSelected(
                                EvaluationChecklist::mandatoryRequirements(),
                                $get('documentary_mandatory')
                            );
                            $otherDone = EvaluationChecklist::countSelected(
                                EvaluationChecklist::otherRequirements(),
                                $get('documentary_other')
                            );
                            $totalDone = $mandatoryDone + $otherDone;
                            $totalAll = EvaluationChecklist::mandatoryCount() + EvaluationChecklist::otherCount();

                            if ($mandatoryDone === EvaluationChecklist::mandatoryCount()) {
                                return new \Illuminate\Support\HtmlString(
                                    '<span style="color:#15803d;font-weight:600;">'
                                    . e("All {$mandatoryDone} Mandatory Requirements complete ({$totalDone} of {$totalAll} total requirements submitted).")
                                    . '</span> '
                                    . e('The QS Evaluation is unlocked beside.')
                                );
                            }

                            return "{$mandatoryDone} of " . EvaluationChecklist::mandatoryCount() . " Mandatory Requirements complete. Complete all mandatory requirements to unlock the QS Evaluation.";
                        }),
                ]),

            Section::make('Qualification Standards')
                ->relationship('evaluation')
                ->icon('heroicon-o-scale')
                ->description("Review the applicant's details against each qualification standard, then mark whether each was Meet the QS or Did not Meet the QS.")
                ->visible(fn (Get $get) => EvaluationChecklist::isDocumentaryComplete($get('evaluation.documentary_mandatory')))
                ->extraAttributes(['class' => 'evaluation-checklist dark-blue-header'])
                ->schema(function () {
                    $boxed = fn (string $title, string $value): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                        '<div style="display:block;width:100%;height:100%;min-height:6rem;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.5rem 0.75rem;">'
                        . '<p style="font-size:1rem;font-weight:700;margin-bottom:0.25rem;">' . e($title) . '</p>'
                        . '<span>' . e($value) . '</span>'
                        . '</div>'
                    );

                    return [
                    Grid::make(2)->schema([
                        Section::make("Bachelor's Degree")
                            ->extraAttributes(['class' => 'qs-box-row'])
                            ->schema([
                                Placeholder::make('qs_education_applicant')
                                    ->hiddenLabel()
                                    ->content(fn ($record) => $boxed('Applicant', EvaluationChecklist::applicantBachelorsDegree($record->application))),

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

                                Hidden::make('qs_education_met'),

                                FormActions::make([
                                    Action::make('qs_education_met_yes')
                                        ->label('Meet the QS')
                                        ->size('sm')
                                        ->color(fn (Get $get) => $get('qs_education_met') === true ? 'success' : 'gray')
                                        ->outlined(fn (Get $get) => $get('qs_education_met') !== true)
                                        ->action(fn (Set $set) => $set('qs_education_met', true)),
                                ])
                                    ->columnStart(1),

                                FormActions::make([
                                    Action::make('qs_education_met_no')
                                        ->label('Did not Meet the QS')
                                        ->size('sm')
                                        ->color(fn (Get $get) => $get('qs_education_met') === false ? 'danger' : 'gray')
                                        ->outlined(fn (Get $get) => $get('qs_education_met') !== false)
                                        ->action(fn (Set $set) => $set('qs_education_met', false)),
                                ])
                                    ->columnStart(2),
                            ])
                            ->columns(2),

                        Section::make('Years of Experience')
                            ->schema([
                                Placeholder::make('qs_experience_applicant')
                                    ->hiddenLabel()
                                    ->content(fn ($record) => $boxed('Applicant', EvaluationChecklist::applicantYearsOfExperience($record->application))),

                                Placeholder::make('qs_experience_standard')
                                    ->hiddenLabel()
                                    ->content(fn ($record) => $boxed('Qualification Standard', ($record->application->jobPosition->min_experience_years ?? 0) . ' minimum year(s)')),

                                Hidden::make('qs_experience_met'),

                                FormActions::make([
                                    Action::make('qs_experience_met_yes')
                                        ->label('Meet the QS')
                                        ->size('sm')
                                        ->color(fn (Get $get) => $get('qs_experience_met') === true ? 'success' : 'gray')
                                        ->outlined(fn (Get $get) => $get('qs_experience_met') !== true)
                                        ->action(fn (Set $set) => $set('qs_experience_met', true)),
                                ])
                                    ->columnStart(1),

                                FormActions::make([
                                    Action::make('qs_experience_met_no')
                                        ->label('Did not Meet the QS')
                                        ->size('sm')
                                        ->color(fn (Get $get) => $get('qs_experience_met') === false ? 'danger' : 'gray')
                                        ->outlined(fn (Get $get) => $get('qs_experience_met') !== false)
                                        ->action(fn (Set $set) => $set('qs_experience_met', false)),
                                ])
                                    ->columnStart(2),
                            ])
                            ->columns(2),

                        Section::make('Hours of Training')
                            ->schema([
                                Placeholder::make('qs_training_applicant')
                                    ->hiddenLabel()
                                    ->content(fn ($record) => $boxed('Applicant', EvaluationChecklist::applicantHoursOfTraining($record->application))),

                                Placeholder::make('qs_training_standard')
                                    ->hiddenLabel()
                                    ->content(fn ($record) => $boxed('Qualification Standard', ($record->application->jobPosition->min_training_hours ?? 0) . ' minimum hour(s)')),

                                Hidden::make('qs_training_met'),

                                FormActions::make([
                                    Action::make('qs_training_met_yes')
                                        ->label('Meet the QS')
                                        ->size('sm')
                                        ->color(fn (Get $get) => $get('qs_training_met') === true ? 'success' : 'gray')
                                        ->outlined(fn (Get $get) => $get('qs_training_met') !== true)
                                        ->action(fn (Set $set) => $set('qs_training_met', true)),
                                ])
                                    ->columnStart(1),

                                FormActions::make([
                                    Action::make('qs_training_met_no')
                                        ->label('Did not Meet the QS')
                                        ->size('sm')
                                        ->color(fn (Get $get) => $get('qs_training_met') === false ? 'danger' : 'gray')
                                        ->outlined(fn (Get $get) => $get('qs_training_met') !== false)
                                        ->action(fn (Set $set) => $set('qs_training_met', false)),
                                ])
                                    ->columnStart(2),
                            ])
                            ->columns(2),

                        Section::make('Eligibility')
                            ->extraAttributes(['class' => 'qs-box-row'])
                            ->schema([
                                Placeholder::make('qs_eligibility_applicant')
                                    ->hiddenLabel()
                                    ->content(fn ($record) => $boxed('Applicant', EvaluationChecklist::applicantEligibility($record->application))),

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

                                Hidden::make('qs_eligibility_met'),

                                FormActions::make([
                                    Action::make('qs_eligibility_met_yes')
                                        ->label('Meet the QS')
                                        ->size('sm')
                                        ->color(fn (Get $get) => $get('qs_eligibility_met') === true ? 'success' : 'gray')
                                        ->outlined(fn (Get $get) => $get('qs_eligibility_met') !== true)
                                        ->action(fn (Set $set) => $set('qs_eligibility_met', true)),
                                ])
                                    ->columnStart(1),

                                FormActions::make([
                                    Action::make('qs_eligibility_met_no')
                                        ->label('Did not Meet the QS')
                                        ->size('sm')
                                        ->color(fn (Get $get) => $get('qs_eligibility_met') === false ? 'danger' : 'gray')
                                        ->outlined(fn (Get $get) => $get('qs_eligibility_met') !== false)
                                        ->action(fn (Set $set) => $set('qs_eligibility_met', false)),
                                ])
                                    ->columnStart(2),
                            ])
                            ->columns(2),
                    ]),
                    ];
                }),

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
                ->relationship('evaluation')
                ->icon('heroicon-o-flag')
                ->extraAttributes(['class' => 'evaluation-checklist dark-blue-header'])
                ->schema([
                    Placeholder::make('result_notice')
                        ->hiddenLabel()
                        ->content(new \Illuminate\Support\HtmlString(
                            '<p style="font-size:0.8rem;color:#64748b;margin-bottom:0.5rem;">'
                            . e('The status defaults to Pending Document Review until mandatory requirements are complete. Applicants are mark Excluded only when specifically using the Exclude button below.')
                            . '</p>'
                        )),

                    Placeholder::make('result_badge')
                        ->hiddenLabel()
                        ->content(function (Get $get, $record) {
                            $result = EvaluationChecklist::computeResult(
                                $get('documentary_mandatory'),
                                $get('qs_education_met'),
                                $get('qs_experience_met'),
                                $get('qs_training_met'),
                                $get('qs_eligibility_met'),
                                currentlyExcluded: $record->result === ApplicationEvaluation::RESULT_EXCLUDED,
                            );

                            // Preview only: once all 4 QS categories are marked Did not
                            // Meet, show Excluded here as an early warning — the actual
                            // result/status still only becomes Excluded once the
                            // evaluator clicks the Exclude Applicant button below.
                            if ($result === ApplicationEvaluation::RESULT_PENDING_DOCUMENT_REVIEW
                                && EvaluationChecklist::isFullyDisqualified(
                                    $get('qs_education_met'),
                                    $get('qs_experience_met'),
                                    $get('qs_training_met'),
                                    $get('qs_eligibility_met'),
                                )) {
                                $result = ApplicationEvaluation::RESULT_EXCLUDED;
                            }

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
                                . e(EvaluationChecklist::resultDescription($result))
                                . '</span>'
                                . '</div>'
                            );
                        })
                        ->columnSpanFull(),

                    Textarea::make('remarks')
                        ->label('Remarks / Notes')
                        ->placeholder('Enter evaluation remarks...')
                        ->rows(4)
                        ->columnSpanFull(),

                    FormActions::make([
                        Action::make('exclude')
                            ->label('Exclude Applicant')
                            ->color('danger')
                            ->icon('heroicon-o-x-circle')
                            ->outlined()
                            ->requiresConfirmation()
                            ->modalHeading('Exclude Applicant')
                            ->modalDescription('This marks the applicant as Excluded from further consideration. You can change this later by re-editing the checklist.')
                            ->modalSubmitActionLabel('Yes, exclude')
                            ->action(function ($livewire) {
                                $application = $livewire->getRecord();

                                $application->evaluation()->update(['result' => ApplicationEvaluation::RESULT_EXCLUDED]);

                                if (in_array($application->status, ['pending', 'evaluated'], true)) {
                                    $application->update(['status' => 'excluded']);

                                    ApplicationStatusLog::create([
                                        'application_id' => $application->id,
                                        'status' => 'excluded',
                                        'remarks' => $application->evaluation->remarks,
                                        'changed_by' => auth()->id(),
                                    ]);
                                }

                                Notification::make()
                                    ->title('Applicant excluded')
                                    ->success()
                                    ->send();

                                return redirect($livewire->getResource()::getUrl('edit', ['record' => $application->id]));
                            }),

                        Action::make('save_evaluation')
                            ->label('Save Changes')
                            ->outlined()
                            ->extraAttributes(['style' => 'box-shadow:inset 0 0 0 1px #1e3a8a;color:#1e3a8a;background-color:transparent;'])
                            ->action(fn ($livewire) => $livewire->save(shouldRedirect: false)),
                    ])
                        ->alignBetween(),
                ])
                ->columns(1),
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
                Tables\Columns\TextColumn::make('evaluation.evaluated_at')
                    ->label('Evaluated')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->placeholder('Not yet'),

                Tables\Columns\IconColumn::make('evaluation.recommended')
                    ->label('Recommended')
                    ->getStateUsing(fn ($record) => $record->status === 'pending' ? null : (bool) $record->evaluation?->recommended)
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'gray',
                        'evaluated' => 'warning',
                        'excluded'  => 'danger',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
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
                Action::make('assign_control_number')
                    ->label('Assign Control #')
                    ->icon('heroicon-o-hashtag')
                    ->color('info')
                    ->visible(fn ($record) => blank($record->controlNumber))
                    ->form([
                        TextInput::make('control_number')
                            ->label('Control Number')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g. DEP-2025-0001'),
                    ])
                    ->action(function ($record, array $data) {
                         
                        $exists = ApplicationControlNumber::where(
                            'control_number', $data['control_number']
                        )->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Control number already in use.')
                                ->danger()
                                ->send();
                            return;
                        }

                        ApplicationControlNumber::create([
                            'application_id' => $record->id,
                            'control_number' => $data['control_number'],
                            'generated_by'   => Auth::id(),
                        ]);

                        $record->update(['status' => 'evaluated']);

                        ApplicationStatusLog::create([
                            'application_id' => $record->id,
                            'status' => 'evaluated',
                            'remarks' => 'Control number assigned.',
                            'changed_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Control number assigned successfully.')
                            ->success()
                            ->send();
                    }),

                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => $record?->profile?->full_name ?? 'Application Details')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalAutofocus(false)
                    ->fillForm(fn ($record) => $record->attributesToArray())
                    ->schema(fn (Schema $schema) => ApplicationDetailsResource::form($schema)),

                Action::make('edit')
                    ->label('Edit Checklist')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record])),
            ])

             
             
             
             
             
             
             
             
             
             
             
             

            ->bulkActions([
                BulkAction::make('export_selected')
                    ->label('Preview & Export Selected IER')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('success')
                    ->modalHeading('Initial Evaluation Result Preview')
                    ->modalDescription('Review the selected records below before creating the Excel workbook.')
                    ->modalWidth(Width::Full)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->modalSubmitActionLabel('Export Excel')
                    ->modalCancelActionLabel('Cancel')
                    ->modalContent(function (EloquentCollection $records) {
                        $applications = Application::query()
                            ->whereIn('id', $records->pluck('id'))
                            ->with([
                                'profile',
                                'jobPosition',
                                'controlNumber',
                                'educations',
                                'trainings',
                                'experiences',
                                'eligibilities',
                                'evaluation',
                            ])
                            ->get();

                        $groups = $applications
                            ->groupBy(fn ($application) => (string) ($application->job_position_id ?? 'unassigned'))
                            ->map(function ($positionApplications): array {
                                $position = $positionApplications->first()?->jobPosition;

                                return [
                                    'position' => IerApplicationFormatter::positionSummary($position),
                                    'rows' => $positionApplications
                                        ->take(10)
                                        ->values()
                                        ->map(fn ($application, int $index) => IerApplicationFormatter::row(
                                            $application,
                                            $index + 1
                                        ))
                                        ->all(),
                                    'total' => $positionApplications->count(),
                                ];
                            })
                            ->values();

                        return view('filament.evaluator.actions.ier-export-preview', [
                            'groups' => $groups,
                            'totalApplications' => $applications->count(),
                        ]);
                    })
                    ->action(function (EloquentCollection $records) {
                        $query = Application::query()->whereIn('id', $records->pluck('id'));

                        return Excel::download(
                            new ApplicationsExport($query),
                            'initial-evaluation-result-selected-'.now()->format('Y-m-d-His').'.xlsx'
                        );
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
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
            'edit'  => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
