<?php

namespace App\Filament\Evaluator\Resources\Applications;

use App\Models\Application;
use App\Models\ApplicationControlNumber;
use App\Filament\Evaluator\Resources\Applications\Pages;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;        // ✅ Layout components
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?string $slug = 'applications';

    public static function getNavigationGroup(): ?string
    {
        return 'Applications';
    }

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Application Info')
                ->icon('heroicon-o-briefcase')
                ->schema([
                    Select::make('job_position_id')
                        ->label('Job Position')
                        ->relationship('jobPosition', 'title')
                        ->disabled(),

                    TextInput::make('status')
                        ->label('Current Status')
                        ->disabled(),
                ])
                ->columns(2),

            Section::make('Control Number')
                ->icon('heroicon-o-hashtag')
                ->description('Assign a control number to this application before marking it as evaluated.')
                ->schema([
                    Placeholder::make('existing_control_number')
                        ->label('Assigned Control Number')
                        ->content(fn ($record) => $record?->controlNumber?->control_number ?? 'Not yet assigned'),

                    Placeholder::make('assigned_by')
                        ->label('Assigned By')
                        ->content(fn ($record) => $record?->controlNumber?->user?->name ?? '—'),
                ])
                ->columns(2),

            Section::make('Applicant Profile')
                ->icon('heroicon-o-user')
                ->schema([
                    Placeholder::make('full_name')
                        ->label('Full Name')
                        ->content(fn ($record) => $record?->profile?->full_name ?? '—'),

                    Placeholder::make('email')
                        ->label('Email Address')
                        ->content(fn ($record) => $record?->profile?->email ?? '—'),

                    Placeholder::make('phone')
                        ->label('Phone Number')
                        ->content(fn ($record) => $record?->profile?->phone ?? '—'),

                    Placeholder::make('address')
                        ->label('Address')
                        ->content(fn ($record) => $record?->profile?->address ?? '—'),

                    Placeholder::make('disability')
                        ->label('Disability (if any)')
                        ->content(fn ($record) => $record?->profile?->disability ?? '—'),

                    Placeholder::make('ethnic_group')
                        ->label('Ethnic Group')
                        ->content(fn ($record) => $record?->profile?->ethnic_group ?? '—'),
                ])
                ->columns(2),

            Section::make('Evaluation Checklist')
                ->icon('heroicon-o-clipboard-document-check')
                ->description('Check each item after verifying the applicant\'s submitted documents.')
                ->schema([
                    Toggle::make('resume_checked')
                        ->label('Resume Checked')
                        ->helperText('Mark if the applicant\'s resume has been reviewed.'),

                    Toggle::make('credentials_valid')
                        ->label('Credentials Valid')
                        ->helperText('Mark if submitted credentials passed verification.'),

                    Toggle::make('recommended')
                        ->label('Recommended for Approval')
                        ->helperText('Mark if this applicant is recommended to proceed.'),
                ])
                ->columns(3),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */
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

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'gray',
                        'evaluated' => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied On')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])

            ->defaultSort('created_at', 'desc')

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'pending'   => 'Pending',
                        'evaluated' => 'Evaluated',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('job_position_id')
                    ->label('Filter by Position')
                    ->relationship('jobPosition', 'title'),
            ])

            ->actions([
                Action::make('assign_control_number')
                    ->label('Assign Control #')
                    ->icon('heroicon-o-hashtag')
                    ->color('info')
                    ->visible(fn ($record) => blank($record->controlNumber))
                    ->form([
                        TextInput::make('control_number')
                            ->label('Control Number')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g. DEP-2025-0001'),
                    ])
                    ->action(function ($record, array $data) {
                        // Manual unique check to avoid the broken query
                        $exists = ApplicationControlNumber::where(
                            'control_number', $data['control_number']
                        )->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Control number already in use.')
                                ->danger()
                                ->send();
                            return;
                        }

                        ApplicationControlNumber::create([
                            'application_id' => $record->id,
                            'control_number' => $data['control_number'],
                            'generated_by'   => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Control number assigned successfully.')
                            ->success()
                            ->send();
                    }),

                Action::make('evaluate')
                    ->label('Mark Evaluated')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'pending' && filled($record->controlNumber))
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Evaluated')
                    ->modalDescription('This forwards the application to admin for final approval. Ensure the control number is assigned and the checklist is complete.')
                    ->action(function ($record) {
                        $record->update(['status' => 'evaluated']);

                        Notification::make()
                            ->title('Application marked as evaluated.')
                            ->success()
                            ->send();
                    }),

                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record])),

                Action::make('edit')
                    ->label('Edit Checklist')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record])),
            ])

            ->bulkActions([]);
    }

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'view'  => Pages\ViewApplication::route('/{record}'),
            'edit'  => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}