<?php

namespace App\Filament\Evaluator\Resources\Approvals;

use App\Exports\ApplicationsExport;
use App\Filament\Resources\Applications\ApplicationResource as ApplicationDetailsResource;
use App\Filament\Evaluator\Resources\Approvals\Pages;
use App\Models\Application;
use App\Support\IerApplicationFormatter;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Facades\Excel;

class ApprovalResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Qualified';

    protected static ?string $modelLabel = 'Qualified Application';

    protected static ?string $pluralModelLabel = 'Qualified';

    protected static ?string $slug = 'approvals';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Applications';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'qualified');
    }

    public static function canCreate(): bool
    {
        return false;
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

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Qualified On')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])

            ->defaultSort('updated_at', 'desc')

            ->recordActionsColumnLabel('Actions')
            ->recordActionsAlignment(\Filament\Support\Enums\Alignment::Center->value)

            ->filters([
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
                    ->schema(fn (Schema $schema) => ApplicationDetailsResource::form($schema)),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovals::route('/'),
        ];
    }
}
