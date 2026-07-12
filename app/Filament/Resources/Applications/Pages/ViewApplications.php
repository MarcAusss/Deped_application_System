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

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'evaluated')
                ->requiresConfirmation()
                ->modalHeading('Approve Application')
                ->modalDescription('Are you sure you want to approve this application? This finalizes the hiring decision.')
                ->modalSubmitActionLabel('Yes, approve')
                ->action(function () {
                    $this->record->update(['status' => 'approved']);

                    $this->record->logs()->create([
                        'status' => 'approved',
                        'changed_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Application approved')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'evaluated')
                ->requiresConfirmation()
                ->modalHeading('Reject Application')
                ->modalDescription('Are you sure you want to reject this application? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, reject')
                ->action(function () {
                    $this->record->update(['status' => 'rejected']);

                    $this->record->logs()->create([
                        'status' => 'rejected',
                        'changed_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Application rejected')
                        ->danger()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
