<?php

namespace App\Filament\Resources\Promos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->dehydrateStateUsing(fn ($state) => $state === null ? null : strtoupper($state))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->options([
                        'percentage' => 'Persentase',
                        'fixed' => 'Nominal',
                    ])
                    ->required()
                    ->live()
                    ->default('percentage'),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->prefix(fn ($get) => $get('type') === 'fixed' ? 'Rp' : '%'),
                TextInput::make('min_purchase')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                TextInput::make('max_discount')
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Maksimal diskon (khusus tipe persentase)'),
                TextInput::make('usage_limit')
                    ->numeric()
                    ->placeholder('Tidak terbatas'),
                TextInput::make('used_count')
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                Toggle::make('is_active')
                    ->default(true),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
            ]);
    }
}
