<?php

namespace App\Filament\Resources\JobPositions;

use App\Models\JobPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use App\Filament\Resources\JobPositions\Pages;

 
use Filament\Actions\Action;

class JobPositionResource extends Resource
{
    protected static ?string $model = JobPosition::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Recruitment';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->required(),

            Forms\Components\Textarea::make('description')
                ->required(),

            Forms\Components\TextInput::make('salary_grade')
                ->label('Salary Grade')
                ->placeholder('e.g. 11'),

            Forms\Components\TextInput::make('monthly_salary')
                ->label('Monthly Salary')
                ->numeric()
                ->prefix('₱'),

            Forms\Components\Textarea::make('education_requirement')
                ->label('Education Requirement')
                ->rows(2),

            Forms\Components\Textarea::make('training_requirement')
                ->label('Training Requirement')
                ->rows(2),

            Forms\Components\Textarea::make('experience_requirement')
                ->label('Experience Requirement')
                ->rows(2),

            Forms\Components\Textarea::make('eligibility_requirement')
                ->label('Eligibility Requirement')
                ->rows(2),

            Forms\Components\Toggle::make('is_open')
                ->label('Available for Hiring')
                ->default(true),

            Grid::make(3)
                ->schema([
                    Forms\Components\DatePicker::make('posted_at')
                        ->label('Posted')
                        ->default(now())
                        ->native(false),

                    Forms\Components\DatePicker::make('until')
                        ->label('Until')
                        ->native(false)
                        ->afterOrEqual('posted_at'),

                    Forms\Components\TextInput::make('until_time')
                        ->label('Closing Time')
                        ->placeholder('e.g. 5:00 PM')
                        ->helperText('Include AM or PM, e.g. 5:00 PM.')
                        ->formatStateUsing(fn (?string $state) => filled($state)
                            ? \Carbon\Carbon::parse($state)->format('g:i A')
                            : null)
                        ->rule(function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (blank($value)) {
                                    return;
                                }

                                try {
                                    \Carbon\Carbon::parse($value);
                                } catch (\Throwable $e) {
                                    $fail('Enter a valid time, e.g. 5:00 PM.');
                                }
                            };
                        })
                        ->dehydrateStateUsing(fn (?string $state) => filled($state)
                            ? \Carbon\Carbon::parse($state)->format('H:i:s')
                            : null),
                ]),

            Forms\Components\FileUpload::make('attachment_path')
                ->label('D.M Notice of Vacancy')
                ->helperText('Upload the official D.M Notice of Vacancy (PDF). Applicants will be able to download this from the job listing.')
                ->disk('public')
                ->directory('job-positions')
                ->acceptedFileTypes(['application/pdf'])
                ->downloadable()
                ->openable(),

            Forms\Components\FileUpload::make('csc_publication_path')
                ->label('CSC Publication of Vacancy')
                ->helperText('Upload the official CSC Publication of Vacancy (PDF). Applicants will be able to download this from the job listing.')
                ->disk('public')
                ->directory('job-positions')
                ->acceptedFileTypes(['application/pdf'])
                ->downloadable()
                ->openable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),

                Tables\Columns\TextColumn::make('salary_grade')
                    ->label('Salary Grade')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_open')
                    ->boolean()
                    ->label('Open'),

                Tables\Columns\ViewColumn::make('application_link')
                    ->label('Application Link')
                    ->view('filament.tables.columns.copy-application-link')
                    ->alignCenter(),
            ])
            ->actions([
                Action::make('toggle')
                    ->label('Toggle Status')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'is_open' => !$record->is_open,
                    ])),

                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                Action::make('delete')
                    ->label('Delete Selected')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->delete()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobPositions::route('/'),
            'create' => Pages\CreateJobPosition::route('/create'),
            'edit' => Pages\EditJobPosition::route('/{record}/edit'),
        ];
    }
}
