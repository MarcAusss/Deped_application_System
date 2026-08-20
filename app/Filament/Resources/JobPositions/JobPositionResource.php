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

            Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('posted_at')
                        ->label('Posted')
                        ->default(now())
                        ->native(false),

                    Forms\Components\DatePicker::make('until')
                        ->label('Until')
                        ->native(false)
                        ->afterOrEqual('posted_at'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
