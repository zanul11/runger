<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SponsorsRelationManager extends RelationManager
{
    protected static string $relationship = 'sponsors';

    protected static ?string $title = 'Sponsors';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            FileUpload::make('logo')->disk('public')
                ->image()
                ->directory('sponsors')
                ->imageEditor()
                ->disk('public')
                ->visibility('public'),
            TextInput::make('link')->url()->placeholder('https://...'),
            Select::make('tier')
                ->options([
                    'title' => 'Title Sponsor',
                    'gold' => 'Gold',
                    'silver' => 'Silver',
                    'partner' => 'Partner',
                ])
                ->default('partner')
                ->required(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
            Textarea::make('note')->columnSpanFull()->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('logo')->disk('public')->circular()->imageWidth(46)->imageHeight(46),
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('tier')->badge()->color(fn (string $state): string => match ($state) {
                    'title' => 'success',
                    'gold' => 'warning',
                    'silver' => 'gray',
                    'partner' => 'info',
                    default => 'gray',
                }),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->label('Tambah Sponsor'),
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
