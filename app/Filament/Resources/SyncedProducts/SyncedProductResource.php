<?php

namespace App\Filament\Resources\SyncedProducts;

use App\Filament\Resources\SyncedProducts\Pages\CreateSyncedProduct;
use App\Filament\Resources\SyncedProducts\Pages\EditSyncedProduct;
use App\Filament\Resources\SyncedProducts\Pages\ListSyncedProducts;
use App\Filament\Resources\SyncedProducts\Pages\ViewSyncedProduct;
use App\Filament\Resources\SyncedProducts\Schemas\SyncedProductForm;
use App\Filament\Resources\SyncedProducts\Schemas\SyncedProductInfolist;
use App\Filament\Resources\SyncedProducts\Tables\SyncedProductsTable;
use App\Models\SyncedProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SyncedProductResource extends Resource
{
    protected static ?string $model = SyncedProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return SyncedProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SyncedProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SyncedProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSyncedProducts::route('/'),
            'create' => CreateSyncedProduct::route('/create'),
            'view' => ViewSyncedProduct::route('/{record}'),
            'edit' => EditSyncedProduct::route('/{record}/edit'),
        ];
    }
}
