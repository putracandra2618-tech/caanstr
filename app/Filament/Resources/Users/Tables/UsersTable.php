<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('is_admin')
                    ->label('Admin')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Admin' : 'Member')
                    ->color(fn (bool $state): string => $state ? 'primary' : 'gray'),
                TextColumn::make('is_banned')
                    ->label('Ban')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Banned' : 'Aktif')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
                TextColumn::make('banned_reason')
                    ->label('Alasan Ban')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_admin')
                    ->label('Role')
                    ->options([
                        '0' => 'Member',
                        '1' => 'Admin',
                    ]),
                SelectFilter::make('is_banned')
                    ->label('Status')
                    ->options([
                        '0' => 'Aktif',
                        '1' => 'Banned',
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
