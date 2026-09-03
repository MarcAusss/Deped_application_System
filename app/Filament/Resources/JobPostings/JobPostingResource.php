<?php

namespace App\Filament\Resources\JobPostings;

use App\Filament\Resources\JobPositions\JobPositionResource;
use App\Filament\Resources\JobPostings\Pages;
use App\Models\JobPosition;
use Filament\Actions\Action;
use Filament\Forms;
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

    protected static ?int $navigationSort = 2;

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
        return parent::getEloquentQuery()->currentlyOpen();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('jp_number')
                    ->label('JP Number')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

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
                        $paths = $record->attachment_paths ?? [];

                        return count($paths) > 0
                            ? collect($paths)->map(fn ($path) => basename($path))->implode(', ')
                            : 'Upload';
                    })
                    ->badge()
                    ->wrap()
                    ->color(fn ($record) => filled($record->attachment_paths) ? Color::Blue : Color::Gray)
                    ->action(
                        Action::make('manageReferences')
                            ->modalHeading('D.M Notice of Vacancy')
                            ->form([
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
                            ])
                            ->fillForm(fn ($record) => [
                                'attachment_paths' => $record->attachment_paths,
                            ])
                            ->action(fn ($record, array $data) => $record->update([
                                'attachment_paths' => $data['attachment_paths'],
                            ]))
                    ),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => JobPositionResource::getUrl('edit', ['record' => $record]) . '?from=job-postings'),
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
