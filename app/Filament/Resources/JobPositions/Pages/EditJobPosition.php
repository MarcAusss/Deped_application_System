<?php

namespace App\Filament\Resources\JobPositions\Pages;

use App\Filament\Resources\JobPostings\JobPostingResource;
use App\Filament\Resources\JobPositions\JobPositionResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Livewire\Attributes\Url;

class EditJobPosition extends EditRecord
{
    protected static string $resource = JobPositionResource::class;

    #[Url]
    public ?string $from = null;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->from === 'job-postings') {
            return JobPostingResource::getUrl();
        }

        return $this->getResource()::getUrl('index');
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }
}
