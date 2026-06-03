<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GalleryRelationManager extends RelationManager
{
    protected static string $relationship = 'gallery';

    protected static ?string $title = 'Dokumentasi';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')->disk('public')
                ->image()
                ->required()
                ->disk('public')
                ->directory('events/gallery')
                ->visibility('public')
                ->imageEditor()
                ->columnSpanFull(),
            TextInput::make('tag')
                ->placeholder('Mis: Squad, Finish Line, Sunrise')
                ->maxLength(60),
            TextInput::make('caption')
                ->placeholder('Caption singkat untuk foto')
                ->maxLength(200)
                ->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_featured')->helperText('Tampil di homepage gallery strip'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caption')
            ->columns([
                ImageColumn::make('image')->disk('public')->imageWidth(80)->imageHeight(80),
                TextColumn::make('tag')->badge()->color('warning'),
                TextColumn::make('caption')->wrap()->searchable(),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('is_featured')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->label('Upload Foto'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
