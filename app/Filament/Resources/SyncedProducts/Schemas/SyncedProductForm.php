<?php

namespace App\Filament\Resources\SyncedProducts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SyncedProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('digiflazz_sku')
                    ->required()
                    ->maxLength(255),
                TextInput::make('digiflazz_price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('brand')
                    ->maxLength(255),
                TextInput::make('type')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->default(true),
                DateTimePicker::make('last_synced_at'),
            ]);
    }
}
