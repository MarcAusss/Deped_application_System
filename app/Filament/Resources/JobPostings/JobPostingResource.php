<?php

namespace App\Filament\Resources\JobPostings;

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

                Tables\Columns\TextColumn::make('view_dm_notice')
                    ->label('View')
                    ->getStateUsing(fn ($record) => filled($record->attachment_path) ? 'D.M notice' : '—')
                    ->color(fn ($record) => filled($record->attachment_path) ? Color::Blue : Color::Gray)
                    ->extraAttributes(fn ($record) => filled($record->attachment_path) ? ['class' => 'hover:underline'] : [])
                    ->url(fn ($record) => filled($record->attachment_path)
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->attachment_path)
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->recordActionsColumnLabel('Actions')
            ->recordActionsAlignment(\Filament\Support\Enums\Alignment::Center->value)
            ->actions([
                Action::make('manage')
                    ->label('Upload / Edit')
                    ->modalHeading('D.M Notice of Vacancy')
                    ->form([
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('D.M Notice of Vacancy')
                            ->helperText('Upload the official D.M Notice of Vacancy (PDF). Applicants will be able to download this from the job listing.')
                            ->disk('public')
                            ->directory('job-positions')
                            ->acceptedFileTypes(['application/pdf'])
                            ->downloadable()
                            ->openable(),
                    ])
                    ->fillForm(fn ($record) => [
                        'attachment_path' => $record->attachment_path,
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'attachment_path' => $data['attachment_path'],
                    ])),
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
