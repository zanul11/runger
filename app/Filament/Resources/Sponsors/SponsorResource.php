<?php

namespace App\Filament\Resources\Sponsors;

use App\Filament\Resources\Sponsors\Pages\CreateSponsor;
use App\Filament\Resources\Sponsors\Pages\EditSponsor;
use App\Filament\Resources\Sponsors\Pages\ListSponsors;
use App\Filament\Resources\Sponsors\Pages\ViewSponsor;
use App\Models\Sponsor;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SponsorResource extends Resource
{
    protected static ?string $model = Sponsor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Sponsors';

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('event_id')
                ->relationship('event', 'title')
                ->required()
                ->preload()
                ->searchable()
                ->label('Event'),
            TextInput::make('name')->required()->maxLength(120),
            FileUpload::make('logo')->disk('public')->image()->directory('sponsors')->imageEditor(),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->disk('public')->circular()->imageWidth(46)->imageHeight(46),
                TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('event.title')->label('Event')->searchable(),
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
            ->filters([
                SelectFilter::make('event_id')->relationship('event', 'title')->label('Event'),
                SelectFilter::make('tier')->options([
                    'title' => 'Title Sponsor',
                    'gold' => 'Gold',
                    'silver' => 'Silver',
                    'partner' => 'Partner',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSponsors::route('/'),
            'create' => CreateSponsor::route('/create'),
            'view' => ViewSponsor::route('/{record}'),
            'edit' => EditSponsor::route('/{record}/edit'),
        ];
    }
}
