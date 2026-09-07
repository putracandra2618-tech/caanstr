<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('midtrans_order_id')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('payment_type')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'settlement', 'capture', 'success' => 'success',
                        'pending', 'challenge' => 'warning',
                        'deny', 'cancel', 'expire', 'failure' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('gross_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('va_number')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('fraud_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accept' => 'success',
                        'challenge', 'deny' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
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
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'settlement' => 'Settlement',
                        'capture' => 'Capture',
                        'challenge' => 'Challenge',
                        'deny' => 'Deny',
                        'cancel' => 'Cancel',
                        'expire' => 'Expire',
                        'failure' => 'Failure',
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
