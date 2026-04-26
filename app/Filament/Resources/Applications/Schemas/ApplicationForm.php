<?php

namespace App\Filament\Resources\Applications\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('job_position_id')
                    ->required()
                    ->numeric(),
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone_number')
                    ->tel()
                    ->required(),
                Textarea::make('resume')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'evaluated' => 'Evaluated',
            'rejected' => 'Rejected',
            'approved' => 'Approved',
        ])
                    ->default('pending')
                    ->required(),
                Toggle::make('evaluated_by_evaluator')
                    ->required(),
                Toggle::make('final_reviewed_by_admin')
                    ->required(),
                Toggle::make('resume_checked')
                    ->required(),
                Toggle::make('credentials_valid')
                    ->required(),
                Toggle::make('recommended')
                    ->required(),
            ]);
    }
}
