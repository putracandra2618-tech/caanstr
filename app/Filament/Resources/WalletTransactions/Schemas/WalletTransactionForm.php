<?php

namespace App\Filament\Resources\WalletTransactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WalletTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->options([
                        'topup' => 'Top Up',
                        'purchase' => 'Pembelian',
                        'refund' => 'Refund',
                        'adjustment' => 'Penyesuaian',
                    ])
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('balance_before')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('balance_after')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                TextInput::make('reference')
                    ->maxLength(255),
            ]);
    }
}
