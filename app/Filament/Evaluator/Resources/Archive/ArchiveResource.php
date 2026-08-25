<?php

namespace App\Filament\Evaluator\Resources\Archive;

use App\Filament\Evaluator\Resources\Applications\ApplicationResource;
use App\Filament\Evaluator\Resources\Archive\Pages;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArchiveResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Archive';

    protected static ?string $modelLabel = 'Archived Application';

    protected static ?string $pluralModelLabel = 'Archive';

    protected static ?string $slug = 'archive';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Applications';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'rejected');
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
                    ->label('Rejected On')
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
                    ->url(fn ($record) => ApplicationResource::getUrl('view', ['record' => $record])),
            ])

            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchive::route('/'),
        ];
    }
}
