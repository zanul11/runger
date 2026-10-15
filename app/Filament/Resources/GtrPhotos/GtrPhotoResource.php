<?php

namespace App\Filament\Resources\GtrPhotos;

use App\Filament\Resources\GtrPhotos\Pages\ListGtrPhotos;
use App\Models\GtrPhoto;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Foto race: upload massal, lalu tandai nomor BIB per foto (ketik langsung di tabel).
 * Foto muncul di "My Photo" Runger App milik peserta dengan BIB tersebut.
 */
class GtrPhotoResource extends Resource
{
    protected static ?string $model = GtrPhoto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static ?string $navigationLabel = 'Foto Race';

    protected static ?string $modelLabel = 'Foto Race';

    protected static ?string $pluralModelLabel = 'Foto Race';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 6;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('path')
                    ->label('Foto')
                    ->disk('public')
                    ->imageHeight(90)
                    ->url(fn (GtrPhoto $r) => $r->url, shouldOpenInNewTab: true),
                TextInputColumn::make('bib_text')
                    ->label('BIB di foto')
                    ->placeholder('mis. 5004, 7012')
                    ->getStateUsing(fn (GtrPhoto $r) => $r->bib_text)
                    ->updateStateUsing(function (GtrPhoto $record, $state) {
                        $record->update(['bibs' => GtrPhoto::parseBibs($state)]);

                        return $record->bib_text;
                    }),
                TextColumn::make('spot')->label('Lokasi')->placeholder('-')->searchable(),
                TextColumn::make('photographer')->label('Fotografer')->placeholder('-')->toggleable(),
                ToggleColumn::make('is_published')->label('Tampil'),
                TextColumn::make('created_at')->label('Diunggah')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('untagged')
                    ->label('Belum ditandai BIB')
                    ->query(fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('bibs')->orWhereJsonLength('bibs', 0))),
                SelectFilter::make('spot')
                    ->label('Lokasi')
                    ->options(fn () => GtrPhoto::query()->whereNotNull('spot')->distinct()->pluck('spot', 'spot')->all()),
                Filter::make('bib')
                    ->label('Cari BIB')
                    ->schema([\Filament\Forms\Components\TextInput::make('bib')->label('BIB')])
                    ->query(fn (Builder $q, array $data) => filled($data['bib'] ?? null) ? $q->whereJsonContains('bibs', (string) $data['bib']) : $q),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->after(fn (GtrPhoto $record) => Storage::disk('public')->delete($record->path)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(fn ($records) => $records->each(fn ($r) => Storage::disk('public')->delete($r->path))),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrPhotos::route('/'),
        ];
    }
}
