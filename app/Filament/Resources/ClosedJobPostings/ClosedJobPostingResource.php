<?php

namespace App\Filament\Resources\ClosedJobPostings;

use App\Filament\Resources\ClosedJobPostings\Pages;
use App\Filament\Resources\JobPositions\JobPositionResource;
use App\Models\JobPosition;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClosedJobPostingResource extends Resource
{
    protected static ?string $model = JobPosition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Closed Postings';

    protected static ?string $modelLabel = 'Closed Job Posting';

    protected static ?string $pluralModelLabel = 'Closed Postings';

    protected static ?string $slug = 'closed-job-postings';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Recruitment';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('posted_at')->closedOrExpired();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
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

                Tables\Columns\TextColumn::make('until')
                    ->label('Until')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->getStateUsing(fn ($record) => $record->hasDeadlinePassed() ? 'Deadline passed' : 'Closed manually')
                    ->badge()
                    ->color(fn ($record) => $record->hasDeadlinePassed() ? Color::Amber : Color::Gray),

                Tables\Columns\TextColumn::make('references')
                    ->label('References')
                    ->getStateUsing(function ($record) {
                        $paths = $record->attachment_paths ?? [];

                        return count($paths) > 0
                            ? collect($paths)->map(fn ($path) => basename($path))->implode(', ')
                            : '—';
                    })
                    ->badge()
                    ->wrap()
                    ->color(fn ($record) => filled($record->attachment_paths) ? Color::Blue : Color::Gray),
            ])
            ->actions([
                Action::make('repost')
                    ->label('Repost')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->modalHeading('Repost Job Position')
                    ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge)
                    ->modalSubmitActionLabel('Repost')
                    ->modalFooterActionsAlignment(\Filament\Support\Enums\Alignment::Right)
                    ->fillForm(fn ($record) => $record->attributesToArray())
                    ->form(JobPositionResource::formFields())
                    ->action(function ($record, array $data) {
                        $data['is_open'] = true;
                        $data['posted_at'] = $data['posted_at'] ?? now()->toDateString();

                        if (blank($record->jp_number)) {
                            $data['jp_number'] = JobPosition::generateJpNumber();
                        }

                        $record->update($data);

                        Notification::make()
                            ->title('Job position reposted')
                            ->body('It is now visible to applicants on the job listing.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClosedJobPostings::route('/'),
        ];
    }
}
