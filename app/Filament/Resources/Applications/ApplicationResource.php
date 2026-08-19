<?php

namespace App\Filament\Resources\Applications;

use App\Models\Application;
use App\Filament\Resources\Applications\Pages;
use App\Filament\Resources\Applications\RelationManagers;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
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
        return parent::getEloquentQuery()->where('status', '!=', 'rejected');
    }

    public static function getRecordRouteBindingEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Rejected applications are excluded from the list query above (they
        // live in the Archive instead), but the View page still needs to
        // resolve them — e.g. from the Archive's "View" link.
        return parent::getEloquentQuery();
    }

    




    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Application Info')
                ->icon('heroicon-o-briefcase')
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
                ->schema([
                    Placeholder::make('control_number')
                        ->label('Assigned Control Number')
                        ->content(fn ($record) => $record?->controlNumber?->control_number ?? 'Not yet assigned'),

                    Placeholder::make('assigned_by')
                        ->label('Assigned By')
                        ->content(fn ($record) => $record?->controlNumber?->user?->name ?? '—'),
                ])
                ->columns(2),

            Section::make('Applicant Profile')
                ->icon('heroicon-o-user')
                ->schema([
                    Placeholder::make('full_name')
                        ->label('Full Name')
                        ->content(fn ($record) => $record?->profile?->full_name ?? '—'),

                    Placeholder::make('email')
                        ->label('Email Address')
                        ->content(fn ($record) => $record?->profile?->email ?? '—'),

                    Placeholder::make('phone')
                        ->label('Phone Number')
                        ->content(fn ($record) => $record?->profile?->phone ?? '—'),

                    Placeholder::make('address')
                        ->label('Address')
                        ->content(fn ($record) => $record?->profile?->address ?? '—'),

                    Placeholder::make('disability')
                        ->label('Disability (if any)')
                        ->content(fn ($record) => $record?->profile?->disability ?? '—'),

                    Placeholder::make('ethnic_group')
                        ->label('Ethnic Group')
                        ->content(fn ($record) => $record?->profile?->ethnic_group ?? '—'),
                ])
                ->columns(2),

                Section::make('Evaluation Results')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->description('Completed by the evaluator. View only — admin cannot edit this.')
                    ->schema([
                        Placeholder::make('resume_checked')
                            ->label('Resume Checked')
                            ->content(fn ($record) => $record?->evaluation?->resume_checked ? 'Yes' : 'No'),

                        Placeholder::make('credentials_valid')
                            ->label('Credentials Valid')
                            ->content(fn ($record) => $record?->evaluation?->credentials_valid ? 'Yes' : 'No'),

                        Placeholder::make('recommended')
                            ->label('Evaluator Recommendation')
                            ->content(fn ($record) => $record?->evaluation?->recommended ? '✓ Recommended' : '✗ Not Recommended'),

                        Placeholder::make('evaluated_by')
                            ->label('Evaluated By')
                            ->content(fn ($record) => $record?->evaluation?->evaluator?->name ?? '—'),

                        Placeholder::make('evaluated_at')
                            ->label('Evaluated On')
                            ->content(fn ($record) => $record?->evaluation?->evaluated_at?->format('M d, Y h:i A') ?? '—'),

                        Placeholder::make('remarks')
                            ->label('Evaluator Remarks')
                            ->content(fn ($record) => $record?->evaluation?->remarks ?? '—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record?->evaluation !== null),

                Section::make('Evaluation Pending')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Placeholder::make('no_evaluation')
                            ->label('')
                            ->content('This application has not been evaluated yet. Approve/Reject will become available once the evaluator submits their checklist.'),
                    ])
                    ->visible(fn ($record) => $record?->evaluation === null),
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
                        'pending'   => 'gray',
                        'evaluated' => 'warning',
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
            ->recordActionsAlignment(\Filament\Support\Enums\Alignment::Center->value)

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'pending'   => 'Pending',
                        'evaluated' => 'Evaluated',
                        'approved'  => 'Approved',
                    ]),

                Tables\Filters\SelectFilter::make('job_position_id')
                    ->label('Filter by Position')
                    ->relationship('jobPosition', 'title'),
            ])

->actions([
    Action::make('view')
        ->label('View')
        ->icon('heroicon-o-eye')
        ->url(fn ($record) => static::getUrl('view', ['record' => $record])),

    Action::make('approve')
        ->label('Approve')
        ->icon('heroicon-o-check-circle')
        ->color(fn ($record) => $record->status === 'evaluated' ? 'success' : 'gray')
        ->disabled(fn ($record) => $record->status !== 'evaluated')
        ->tooltip(fn ($record) => $record->status !== 'evaluated' ? 'Only evaluated applications can be approved.' : null)
        ->requiresConfirmation()
        ->modalHeading('Approve Application')
        ->modalDescription('Are you sure you want to approve this application? This finalizes the hiring decision.')
        ->modalSubmitActionLabel('Yes, approve')
        ->action(function ($record) {
            $record->update(['status' => 'approved']);

            $record->logs()->create([
                'status' => 'approved',
                'changed_by' => auth()->id(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Application approved')
                ->success()
                ->send();
        }),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color(fn ($record) => $record->status === 'evaluated' ? 'danger' : 'gray')
                ->disabled(fn ($record) => $record->status !== 'evaluated')
                ->tooltip(fn ($record) => $record->status !== 'evaluated' ? 'Only evaluated applications can be rejected.' : null)
                ->requiresConfirmation()
                ->modalHeading('Reject Application')
                ->modalDescription('Are you sure you want to reject this application? This application will be move to archive.')
                ->modalSubmitActionLabel('Yes, reject')
                ->action(function ($record) {
                    $record->update(['status' => 'rejected']);

                    $record->logs()->create([
                        'status' => 'rejected',
                        'changed_by' => auth()->id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Application rejected')
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
