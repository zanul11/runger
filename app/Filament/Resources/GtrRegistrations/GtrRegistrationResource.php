<?php

namespace App\Filament\Resources\GtrRegistrations;

use App\Filament\Resources\GtrRegistrations\Pages\EditGtrRegistration;
use App\Filament\Resources\GtrRegistrations\Pages\ListGtrRegistrations;
use App\Filament\Resources\GtrRegistrations\Pages\ViewGtrRegistration;
use App\Mail\RegistrationConfirmation;
use App\Models\GtrRegistration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GtrRegistrationResource extends Resource
{
    protected static ?string $model = GtrRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Pendaftar GTR';

    protected static ?string $modelLabel = 'Pendaftar GTR';

    protected static ?string $pluralModelLabel = 'Pendaftar GTR';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 2;

    protected static array $statuses = ['pending' => 'Pending', 'paid' => 'Paid', 'cancelled' => 'Cancelled'];

    public static function getNavigationBadge(): ?string
    {
        return (string) GtrRegistration::where('payment_status', 'pending')->count() ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status & BIB')->columns(3)->components([
                Select::make('gtr_category_id')->label('Kategori')
                    ->relationship('category', 'name')
                    ->required()
                    ->native(false)
                    ->columnSpan(3),
                TextInput::make('nomor_registrasi')->disabled(),
                Select::make('payment_status')->label('Status Pembayaran')->options(self::$statuses)->required()
                    ->helperText('Jika "Paid": BIB & email konfirmasi otomatis.'),
                TextInput::make('bib_number')->label('BIB')
                    ->helperText('Kosongkan — otomatis dibuat saat lunas.'),
            ]),
            Section::make('Akun Peserta (login)')
                ->description('Password untuk akun peserta. Bila email sudah terdaftar, password diperbarui.')
                ->columns(2)
                ->visibleOn('create')
                ->components([
                    TextInput::make('password')->label('Password Akun')
                        ->password()->revealable()
                        ->required()
                        ->minLength(6),
                ]),
            Section::make('Data Peserta')->columns(2)->components([
                TextInput::make('full_name')->label('Nama Lengkap')->required()->columnSpanFull(),
                TextInput::make('nik')->label('NIK'),
                Select::make('size')->label('Ukuran Jersey')->options(array_combine(GtrRegistration::SIZES, GtrRegistration::SIZES)),
                TextInput::make('email')->email()->required()
                    ->helperText('Email = username akun peserta untuk login.'),
                TextInput::make('whatsapp')->tel(),
                DatePicker::make('birth_date')->label('Tanggal Lahir'),
                Select::make('gender')->options(array_combine(GtrRegistration::GENDERS, GtrRegistration::GENDERS)),
                Select::make('blood_type')->label('Gol. Darah')->options(array_combine(GtrRegistration::BLOOD_TYPES, GtrRegistration::BLOOD_TYPES)),
                TextInput::make('club')->label('Klub'),
                TextInput::make('address')->label('Alamat')->columnSpanFull(),
                TextInput::make('emergency_name')->label('Kontak Darurat (Nama)'),
                TextInput::make('emergency_contact')->label('Kontak Darurat (No.)'),
            ]),
            Section::make('Pembayaran')->columns(2)->components([
                Select::make('pay')->label('Metode Pembayaran')
                    ->options(GtrRegistration::PAY_METHODS)
                    ->default('Cash')
                    ->native(false)
                    ->helperText('QRIS dikenakan biaya layanan; metode lain tidak (biaya layanan = 0).'),
                TextInput::make('amount')->label('Nominal')->numeric()->prefix('IDR'),
                TextInput::make('midtrans_order_id')->label('Order ID Midtrans')->disabled(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('nomor_registrasi'),
            TextEntry::make('payment_status')->badge()->formatStateUsing(fn ($state) => self::$statuses[$state] ?? $state)
                ->color(fn ($state) => match ($state) { 'paid' => 'success', 'cancelled' => 'danger', default => 'warning' }),
            TextEntry::make('bib_number')->label('BIB')->placeholder('-'),
            TextEntry::make('category.distance')->label('Kategori')->formatStateUsing(fn ($state, $record) => trim(($state ?? '') . ' · ' . ($record->category->name ?? ''))),
            TextEntry::make('full_name')->label('Nama'),
            TextEntry::make('nik')->label('NIK'),
            TextEntry::make('size')->label('Jersey'),
            TextEntry::make('email'),
            TextEntry::make('whatsapp'),
            TextEntry::make('birth_date')->date(),
            TextEntry::make('gender'),
            TextEntry::make('blood_type')->label('Gol. Darah'),
            TextEntry::make('club')->placeholder('-'),
            TextEntry::make('address')->columnSpanFull(),
            TextEntry::make('emergency_name')->label('Kontak Darurat'),
            TextEntry::make('emergency_contact')->label('No. Darurat'),
            TextEntry::make('pay')->label('Metode Bayar'),
            TextEntry::make('amount')->money('IDR'),
            TextEntry::make('paid_at')->dateTime()->placeholder('-'),
            TextEntry::make('registered_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_registrasi')->label('No. Reg')->searchable()->copyable(),
                TextColumn::make('full_name')->label('Nama')->searchable(),
                TextColumn::make('category.distance')->label('Kategori')
                    ->formatStateUsing(fn ($state, $record) => trim(($state ?? '') . ' · ' . ($record->category->name ?? '')))
                    ->toggleable(),
                TextColumn::make('size')->label('Jersey')->badge()->toggleable(),
                TextColumn::make('amount')->money('IDR')->sortable()->toggleable(),
                TextColumn::make('payment_status')->label('Pembayaran')->badge()
                    ->formatStateUsing(fn ($state) => self::$statuses[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'paid' => 'success', 'cancelled' => 'danger', default => 'warning' }),
                TextColumn::make('bib_number')->label('BIB')->placeholder('-')->toggleable(),
                TextColumn::make('race_status')->label('Status Lomba')->badge()
                    ->color(fn ($state) => match ($state) {
                        'finisher' => 'success', 'dnf' => 'warning', 'dq' => 'danger', 'dns' => 'gray', default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('registered_at')->label('Daftar')->dateTime()->sortable(),
            ])
            ->defaultSort('registered_at', 'desc')
            ->filters([
                SelectFilter::make('payment_status')->label('Status')->options(self::$statuses),
                SelectFilter::make('gtr_category_id')->label('Kategori')->relationship('category', 'name'),
            ])
            ->recordActions([
                Action::make('kirimEmail')
                    ->label('Kirim Email')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Email Konfirmasi')
                    ->modalDescription(fn (GtrRegistration $record) => 'Email konfirmasi akan dikirim ke ' . $record->email . '.')
                    ->action(function (GtrRegistration $record) {
                        try {
                            Mail::to($record->email)->send(new RegistrationConfirmation($record->load('category')));

                            Notification::make()
                                ->title('Email terkirim')
                                ->body('Konfirmasi dikirim ke ' . $record->email)
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal mengirim email')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
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

    public static function getPages(): array
    {
        return [
            'index' => ListGtrRegistrations::route('/'),
            'create' => \App\Filament\Resources\GtrRegistrations\Pages\CreateGtrRegistration::route('/create'),
            'view' => ViewGtrRegistration::route('/{record}'),
            'edit' => EditGtrRegistration::route('/{record}/edit'),
        ];
    }
}
