<?php

namespace App\Filament\Resources\AutoTopupLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AutoTopupLogForm
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
                TextInput::make('provider')
                    ->required()
                    ->maxLength(255),
                KeyValue::make('request_data')
                    ->columnSpanFull(),
                KeyValue::make('response_data')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])
                    ->default('pending')
                    ->required(),
                TextInput::make('attempts')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('error_message')
                    ->columnSpanFull(),
                DateTimePicker::make('processed_at'),
            ]);
    }
}
