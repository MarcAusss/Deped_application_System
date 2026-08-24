<?php

namespace App\Filament\Resources\JobPostings;

use App\Filament\Resources\JobPositions\JobPositionResource;
use App\Filament\Resources\JobPostings\Pages;
use App\Models\JobPosition;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;

class JobPostingResource extends Resource
{
    protected static ?string $model = JobPosition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Job-Posting';

    protected static ?string $modelLabel = 'Job Posting';

    protected static ?string $pluralModelLabel = 'Job-Posting';

    protected static ?string $slug = 'job-postings';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Applications';
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

                Tables\Columns\TextColumn::make('attachment_path')
                    ->label('D.M Notice of Vacancy')
                    ->getStateUsing(fn ($record) => filled($record->attachment_path) ? 'Uploaded' : 'Not uploaded')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Uploaded' ? 'success' : 'gray'),
            ])
            ->recordActionsColumnLabel('Actions')
            ->recordActionsAlignment(\Filament\Support\Enums\Alignment::Center->value)
            ->actions([
                Action::make('view')
                    ->label('View D.M Notice')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color(Color::Blue)
                    ->visible(fn ($record) => filled($record->attachment_path))
                    ->url(fn ($record) => \Illuminate\Support\Facades\Storage::disk('public')->url($record->attachment_path))
                    ->openUrlInNewTab(),

                Action::make('manage')
                    ->label('Upload / Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => JobPositionResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobPostings::route('/'),
        ];
    }
}
