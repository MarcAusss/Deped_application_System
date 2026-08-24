<?php

namespace App\Filament\Resources\PublicationOfVacancy;

use App\Filament\Resources\PublicationOfVacancy\Pages;
use App\Models\JobPosition;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class PublicationOfVacancyResource extends Resource
{
    protected static ?string $model = JobPosition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Publication of Vacancy (CSC)';

    protected static ?string $modelLabel = 'Publication of Vacancy';

    protected static ?string $pluralModelLabel = 'Publication of Vacancy (CSC)';

    protected static ?string $slug = 'publication-of-vacancy';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Recruitment';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Job Position')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_open')
                    ->boolean()
                    ->label('Open'),

                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Posted')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('until')
                    ->label('Until')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('csc_publication_path')
                    ->label('CSC Publication of Vacancy')
                    ->getStateUsing(fn ($record) => filled($record->csc_publication_path) ? 'Uploaded' : 'Not uploaded')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Uploaded' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('view_csc_publication')
                    ->label('View')
                    ->getStateUsing(fn ($record) => filled($record->csc_publication_path) ? 'Publication of CSC' : '—')
                    ->color(fn ($record) => filled($record->csc_publication_path) ? Color::Blue : Color::Gray)
                    ->extraAttributes(fn ($record) => filled($record->csc_publication_path) ? ['class' => 'hover:underline'] : [])
                    ->url(fn ($record) => filled($record->csc_publication_path)
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->csc_publication_path)
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->recordActionsColumnLabel('Actions')
            ->recordActionsAlignment(\Filament\Support\Enums\Alignment::Center->value)
            ->actions([
                Action::make('manage')
                    ->label('Upload / Edit')
                    ->modalHeading('CSC Publication of Vacancy')
                    ->form([
                        Forms\Components\FileUpload::make('csc_publication_path')
                            ->label('CSC Publication of Vacancy')
                            ->helperText('Upload the official CSC Publication of Vacancy (PDF). Applicants will be able to download this from the job listing.')
                            ->disk('public')
                            ->directory('job-positions')
                            ->acceptedFileTypes(['application/pdf'])
                            ->downloadable()
                            ->openable(),
                    ])
                    ->fillForm(fn ($record) => [
                        'csc_publication_path' => $record->csc_publication_path,
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'csc_publication_path' => $data['csc_publication_path'],
                    ])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublicationOfVacancy::route('/'),
        ];
    }
}
