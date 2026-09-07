<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
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
                TextInput::make('order_number')
                    ->maxLength(255)
                    ->disabled()
                    ->helperText('Diisi otomatis jika dikosongkan pada saat create.'),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('game_id')
                    ->label('ID Game')
                    ->required(),
                TextInput::make('game_zone')
                    ->label('Zone Game'),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('subtotal')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                TextInput::make('admin_fee')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                TextInput::make('discount')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                TextInput::make('total')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default(OrderStatus::Pending)
                    ->required(),
                TextInput::make('payment_method'),
                TextInput::make('snap_token')
                    ->disabled()
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at')
                    ->disabled(),
                DateTimePicker::make('completed_at')
                    ->disabled(),
                Textarea::make('failed_reason')
                    ->columnSpanFull(),
            ]);
    }
}
