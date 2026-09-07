<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('midtrans_order_id')
                    ->label('Midtrans Order ID')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('edit'),
                TextInput::make('payment_type'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('gross_amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->disabledOn('edit'),
                TextInput::make('va_number'),
                TextInput::make('fraud_status'),
                KeyValue::make('raw_response')
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
