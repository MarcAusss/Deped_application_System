<?php

namespace App\Filament\Evaluator\Resources\Applications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Submitted Documents';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Document Type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_path')
                    ->label('File')
                    ->formatStateUsing(fn ($state) => basename($state))
                    ->url(fn ($record) => Storage::url($record->file_path))
                    ->openUrlInNewTab()
                    ->tooltip('Click to open file'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('type')
                ->label('Document Type')
                ->options([
                    'Resume'        => 'Resume',
                    'Diploma'       => 'Diploma',
                    'Transcript'    => 'Transcript of Records',
                    'Certificate'   => 'Certificate',
                    'Government ID' => 'Government ID',
                    'Other'         => 'Other',
                ])
                ->required(),

            Forms\Components\FileUpload::make('file_path')
                ->label('Upload File')
                ->directory('applications/documents')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                ->maxSize(5120)
                ->required(),
        ]);
    }
}