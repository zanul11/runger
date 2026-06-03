<?php

namespace App\Filament\Resources\Events;

use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\Pages\ViewEvent;
use App\Filament\Resources\Events\RelationManagers\GalleryRelationManager;
use App\Filament\Resources\Events\RelationManagers\RoutesRelationManager;
use App\Filament\Resources\Events\RelationManagers\SponsorsRelationManager;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Events';

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Event')
                ->columns(2)
                ->components([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Auto dari title. URL-safe.'),
                    TextInput::make('subtitle')->maxLength(150),
                    TextInput::make('tag')->maxLength(80)->helperText('Mis: "Long Run · Free Refreshment"'),
                ]),

            Section::make('Tanggal & Lokasi')
                ->columns(2)
                ->components([
                    DatePicker::make('date')->required()->native(false),
                    TimePicker::make('time')->seconds(false)->default('05:30')->required(),
                    TextInput::make('location')->maxLength(120),
                    TextInput::make('tikum')->label('Titik Kumpul')->maxLength(160),
                ]),

            Section::make('Detail Lomba')
                ->columns(2)
                ->components([
                    TextInput::make('distance_text')->label('Jarak')->placeholder('10K / 5K · 12K · 21K'),
                    TextInput::make('pace'),
                    TextInput::make('fee'),
                    TextInput::make('briefing'),
                    Textarea::make('note')->columnSpanFull()->rows(3)->helperText('Ditampilkan di card agenda.'),
                ]),

            Section::make('Poster')
                ->components([
                    FileUpload::make('poster_image')->disk('public')
                        ->image()
                        ->disk('public')
                        ->directory('events/poster')
                        ->visibility('public')
                        ->imageEditor(),
                ]),

            Section::make('Tampilan')
                ->columns(2)
                ->components([
                    Toggle::make('is_coming_soon')
                        ->label('Coming Soon')
                        ->helperText('Centang kalau detail belum siap diumumkan. Selain itu status otomatis dihitung dari tanggal (upcoming / completed).'),
                    Toggle::make('is_featured')->helperText('Card dengan border volt'),
                    Toggle::make('is_published')->default(true),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),

            Section::make('Call to Action (Optional)')
                ->columns(2)
                ->collapsed()
                ->components([
                    TextInput::make('cta_primary_label')->default('Info via Instagram'),
                    TextInput::make('cta_primary_href')->url()->placeholder('https://www.instagram.com/runnersgerung/'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('poster_image')->disk('public')->circular()->imageWidth(46)->imageHeight(46),
                TextColumn::make('title')->searchable()->sortable()->weight('bold'),
                TextColumn::make('date')->date('d M Y')->sortable(),
                TextColumn::make('distance_text')->label('Jarak')->badge(),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'upcoming' => 'success',
                    'coming_soon' => 'warning',
                    'completed' => 'gray',
                    default => 'gray',
                })->formatStateUsing(fn (string $state): string => match ($state) {
                    'upcoming' => 'Upcoming',
                    'coming_soon' => 'Coming Soon',
                    'completed' => 'Selesai',
                    default => ucfirst($state),
                })->tooltip('Otomatis dari tanggal · "Coming Soon" via toggle'),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                IconColumn::make('is_published')->boolean()->label('Published'),
            ])
            ->defaultSort('date', 'asc')
            ->filters([
                SelectFilter::make('is_coming_soon')->label('Coming Soon')
                    ->options(['1' => 'Ya', '0' => 'Tidak']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SponsorsRelationManager::class,
            RoutesRelationManager::class,
            GalleryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'view' => ViewEvent::route('/{record}'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
