<?php

namespace App\Filament\Resources\Runners;

use App\Filament\Resources\Runners\Pages\ListRunners;
use App\Filament\Resources\Runners\Pages\ViewRunner;
use App\Models\Runner;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RunnerResource extends Resource
{
    protected static ?string $model = Runner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Akun Peserta';

    protected static ?string $modelLabel = 'Akun Peserta';

    protected static ?string $pluralModelLabel = 'Akun Peserta';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('first_name')->label('Nama Depan'),
            TextEntry::make('last_name')->label('Nama Belakang')->placeholder('-'),
            TextEntry::make('email'),
            TextEntry::make('phone')->label('No. HP')->formatStateUsing(fn ($state, $record) => $state ? ($record?->phone_country . ' ' . $state) : '-'),
            TextEntry::make('nationality')->placeholder('-'),
            TextEntry::make('birthdate')->date()->placeholder('-'),
            TextEntry::make('gender')->placeholder('-'),
            TextEntry::make('address')->placeholder('-')->columnSpanFull(),
            TextEntry::make('registrations_count')->label('Jumlah Pendaftaran')->state(fn ($record) => $record->registrations()->count()),
            TextEntry::make('created_at')->label('Bergabung')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Nama')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->label('No. HP')->toggleable(),
                TextColumn::make('nationality')->toggleable(),
                TextColumn::make('registrations_count')->label('Pendaftaran')->counts('registrations')->badge(),
                TextColumn::make('created_at')->label('Bergabung')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                static::setPasswordAction(),
                static::sendResetLinkAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    // Admin menentukan password baru, lalu menyampaikannya sendiri ke peserta.
    public static function setPasswordAction(): Action
    {
        return Action::make('setPassword')
            ->label('Set Password')
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->modalHeading('Set Password Baru')
            ->modalDescription(fn (Runner $record) => 'Password akun ' . $record->email . ' akan diganti. Sampaikan password baru ke peserta dan sarankan untuk menggantinya di halaman Profil.')
            ->schema([
                TextInput::make('password')
                    ->label('Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::min(8)->letters()->mixedCase()->numbers())
                    ->helperText('Minimal 8 karakter, ada huruf besar & kecil, dan angka.')
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false),
            ])
            ->action(function (Runner $record, array $data) {
                $record->forceFill(['password' => $data['password']])->setRememberToken(Str::random(60));
                $record->save();

                Notification::make()
                    ->title('Password berhasil diganti')
                    ->body('Akun ' . $record->email)
                    ->success()
                    ->send();
            });
    }

    // Kirim email berisi link reset; peserta membuat password barunya sendiri.
    public static function sendResetLinkAction(): Action
    {
        return Action::make('sendResetLink')
            ->label('Kirim Link Reset')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Kirim Link Reset Password')
            ->modalDescription(fn (Runner $record) => 'Email berisi link untuk membuat password baru (berlaku ' . config('auth.passwords.runners.expire') . ' menit) akan dikirim ke ' . $record->email . '.')
            ->action(function (Runner $record) {
                try {
                    $status = PasswordBroker::broker('runners')->sendResetLink(['email' => $record->email]);
                } catch (\Throwable $e) {
                    Log::error('Gagal kirim link reset password peserta: ' . $e->getMessage());
                    $status = null;
                }

                match ($status) {
                    PasswordBroker::RESET_LINK_SENT => Notification::make()->title('Link reset terkirim')->body('Dikirim ke ' . $record->email)->success()->send(),
                    PasswordBroker::RESET_THROTTLED => Notification::make()->title('Link baru saja dikirim')->body('Tunggu 1 menit sebelum mengirim lagi.')->warning()->send(),
                    default => Notification::make()->title('Gagal mengirim (cek log/SMTP)')->danger()->send(),
                };
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRunners::route('/'),
            'view' => ViewRunner::route('/{record}'),
        ];
    }
}
