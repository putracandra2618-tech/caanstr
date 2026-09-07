<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->tel()
                    ->placeholder('08xxxxxxxxxx'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                TextInput::make('balance')
                    ->label('Saldo')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->prefix('Rp')
                    ->disabled(),
                TextInput::make('avatar'),
                Toggle::make('is_admin')
                    ->label('Admin')
                    ->required(),
                Toggle::make('is_banned')
                    ->label('Di-ban')
                    ->live()
                    ->rule(fn (string $operation, ?User $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($operation, $record): void {
                        if ($value && $operation === 'edit' && $record?->is_admin) {
                            $fail('Admin tidak dapat di-ban.');
                        }
                    }),
                DateTimePicker::make('banned_at')
                    ->disabled()
                    ->helperText('Diisi otomatis berdasarkan status ban.'),
                Textarea::make('banned_reason')
                    ->columnSpanFull()
                    ->placeholder('Alasan pemban (opsional)'),
                DateTimePicker::make('email_verified_at')
                    ->label('Email terverifikasi pada'),
            ]);
    }
}
