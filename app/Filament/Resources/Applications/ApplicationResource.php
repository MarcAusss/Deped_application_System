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

    /*
    |--------------------------------------------------------------------------
    | FORM (Read-only view of application details)
    |--------------------------------------------------------------------------
    */
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

            Section::make('Evaluation Checklist')
                ->icon('heroicon-o-clipboard-document-check')
                ->description('Completed by the evaluator.')
                ->schema([
                    Toggle::make('resume_checked')
                        ->label('Resume Checked')
                        ->disabled(),

                    Toggle::make('credentials_valid')
                        ->label('Credentials Valid')
                        ->disabled(),

                    Toggle::make('recommended')
                        ->label('Recommended for Approval')
                        ->disabled(),
                ])
                ->columns(3),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */
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

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'pending'   => 'Pending',
                        'evaluated' => 'Evaluated',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
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
            ])

            ->bulkActions([]);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATION MANAGERS
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'view'  => Pages\ViewApplication::route('/{record}'),
        ];
    }
}