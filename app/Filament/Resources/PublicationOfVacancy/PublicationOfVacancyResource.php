<?php

namespace App\Filament\Resources\PublicationOfVacancy;

use App\Filament\Resources\JobPositions\JobPositionResource;
use App\Filament\Resources\PublicationOfVacancy\Pages;
use App\Models\JobPosition;
use Filament\Actions\Action;
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereNotNull('posted_at');
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
                    ->label('Open')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->is_open && ! $record->hasDeadlinePassed()),

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

                Tables\Columns\TextColumn::make('references')
                    ->label('References')
                    ->getStateUsing(function ($record) {
                        $paths = $record->csc_publication_paths ?? [];

                        return count($paths) > 0
                            ? collect($paths)->map(fn ($path) => basename($path))->implode(', ')
                            : 'No file';
                    })
                    ->badge()
                    ->wrap()
                    ->color(fn ($record) => filled($record->csc_publication_paths) ? Color::Blue : Color::Gray)
                    ->action(
                        Action::make('viewReferences')
                            ->modalHeading('CSC Publication of Vacancy')
                            ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge)
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalContent(fn ($record) => view('filament.tables.columns.references-preview', [
                                'paths' => $record->csc_publication_paths ?? [],
                            ]))
                    ),
            ])
            ->recordActionsColumnLabel('Actions')
            ->recordActionsAlignment(\Filament\Support\Enums\Alignment::Center->value)
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => JobPositionResource::getUrl('edit', ['record' => $record])),
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
