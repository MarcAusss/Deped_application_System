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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|null|\UnitEnum $navigationGroup = 'Recruitment';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::formFields());
    }

    public static function formFields(): array
    {
        return [
            Grid::make(3)->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpan(2),

                Forms\Components\TextInput::make('abbreviation')
                    ->label('Acronym')
                    ->placeholder('e.g. T-I'),

                Forms\Components\TextInput::make('slots')
                    ->label('No. of Vacancies')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1)
                    ->suffix('slot(s)')
                    ->columnSpan(3),
            ]),

            Forms\Components\Textarea::make('description')
                ->required(),

            Grid::make(3)
                ->columnSpanFull()
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
                        ->placeholder('e.g. 5:00 PM, 12 midnight, or 12 noon')
                        ->formatStateUsing(fn (?string $state) => filled($state)
                            ? \Carbon\Carbon::parse($state)->format('g:i A')
                            : null)
                        ->rule(function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (blank($value)) {
                                    return;
                                }

                                try {
                                    \Carbon\Carbon::parse(static::normalizeClosingTime($value));
                                } catch (\Throwable $e) {
                                    $fail('Enter a valid time, e.g. 5:00 PM, 12 midnight, or 12 noon.');
                                }
                            };
                        })
                        ->dehydrateStateUsing(fn (?string $state) => filled($state)
                            ? \Carbon\Carbon::parse(static::normalizeClosingTime($state))->format('H:i:s')
                            : null),
                ]),

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

            Forms\Components\TextInput::make('min_training_hours')
                ->label('Minimum Training Hours (QS)')
                ->numeric()
                ->minValue(0)
                ->suffix('hour(s)'),

            Forms\Components\Textarea::make('experience_requirement')
                ->label('Experience Requirement')
                ->rows(2),

            Forms\Components\TextInput::make('min_experience_years')
                ->label('Minimum Years of Experience (QS)')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->suffix('year(s)'),

            Forms\Components\Textarea::make('eligibility_requirement')
                ->label('Eligibility Requirement')
                ->rows(2),

            Forms\Components\FileUpload::make('attachment_paths')
                ->label('D.M Notice of Vacancy')
                ->helperText('Upload the official D.M Notice of Vacancy. You can select or drag in multiple PDF files (or an entire folder of PDFs) at once. Applicants will be able to download each of these from the job listing.')
                ->multiple()
                ->disk('public')
                ->directory('job-positions')
                ->preserveFilenames()
                ->acceptedFileTypes(['application/pdf'])
                ->downloadable()
                ->openable()
                ->reorderable()
                ->panelLayout('grid'),

            Forms\Components\FileUpload::make('csc_publication_path')
                ->label('CSC Publication of Vacancy')
                ->helperText('Upload the official CSC Publication of Vacancy (PDF). Applicants will be able to download this from the job listing.')
                ->disk('public')
                ->directory('job-positions')
                ->acceptedFileTypes(['application/pdf'])
                ->downloadable()
                ->openable(),
        ];
    }

    /**
     * Lets "12 midnight" / "12 noon" (or plain "midnight" / "noon") be entered
     * as unambiguous alternatives to 12:00 AM / 12:00 PM.
     */
    public static function normalizeClosingTime(string $value): string
    {
        $normalized = preg_replace('/^12\s+(midnight|noon)$/i', '$1', trim($value));

        return $normalized ?? $value;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('abbreviation')
                    ->label('Acronym')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Posted')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('slots')
                    ->label('No. of Vacancies')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_open')
                    ->boolean()
                    ->label('Open'),
            ])
            ->actions([
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
