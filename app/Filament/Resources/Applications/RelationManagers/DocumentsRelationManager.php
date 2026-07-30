<?php

namespace App\Filament\Resources\Applications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
                ->disk('public')
                ->directory('applications/documents')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(5120)
                ->required(),
        ]);
    }
}