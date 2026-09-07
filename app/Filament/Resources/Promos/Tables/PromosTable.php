<?php

namespace App\Filament\Resources\Promos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'percentage' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state): string => $state === 'percentage' ? 'Persentase' : 'Nominal'),
                TextColumn::make('value')
                    ->formatStateUsing(function ($record): string {
                        if ($record->type === 'percentage') {
                            return number_format((float) $record->value, 0, ',', '.').'%';
                        }

                        return 'Rp '.number_format((float) $record->value, 0, ',', '.');
                    })
                    ->sortable(),
                TextColumn::make('min_purchase')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('max_discount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('usage_limit')
                    ->numeric()
                    ->sortable()
                    ->placeholder('∞'),
                TextColumn::make('used_count')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filament\Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
