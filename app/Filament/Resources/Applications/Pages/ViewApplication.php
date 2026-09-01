<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('waiting')
                ->label('Waiting for Evaluation')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->disabled()
                ->visible(fn () => $this->record->status === 'pending'),

            Action::make('qualify')
                ->label('Qualified')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'evaluated')
                ->requiresConfirmation()
                ->modalHeading('Mark Application Qualified')
                ->modalDescription('Are you sure you want to mark this application Qualified? This finalizes the hiring decision.')
                ->modalSubmitActionLabel('Yes, mark Qualified')
                ->action(function () {
                    $this->record->update(['status' => 'qualified']);

                    $this->record->logs()->create([
                        'status' => 'qualified',
                        'changed_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Application marked Qualified')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('disqualify')
                ->label('Disqualified')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'evaluated')
                ->requiresConfirmation()
                ->modalHeading('Mark Application Disqualified')
                ->modalDescription('Are you sure you want to mark this application Disqualified? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, mark Disqualified')
                ->action(function () {
                    $this->record->update(['status' => 'disqualified']);

                    $this->record->logs()->create([
                        'status' => 'disqualified',
                        'changed_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Application marked Disqualified')
                        ->danger()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
