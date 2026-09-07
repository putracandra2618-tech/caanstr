<?php

namespace App\Filament\Resources\AutoTopupLogs;

use App\Filament\Resources\AutoTopupLogs\Pages\CreateAutoTopupLog;
use App\Filament\Resources\AutoTopupLogs\Pages\EditAutoTopupLog;
use App\Filament\Resources\AutoTopupLogs\Pages\ListAutoTopupLogs;
use App\Filament\Resources\AutoTopupLogs\Pages\ViewAutoTopupLog;
use App\Filament\Resources\AutoTopupLogs\Schemas\AutoTopupLogForm;
use App\Filament\Resources\AutoTopupLogs\Schemas\AutoTopupLogInfolist;
use App\Filament\Resources\AutoTopupLogs\Tables\AutoTopupLogsTable;
use App\Models\AutoTopupLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AutoTopupLogResource extends Resource
{
    protected static ?string $model = AutoTopupLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return AutoTopupLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AutoTopupLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AutoTopupLogsTable::configure($table);
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
            'index' => ListAutoTopupLogs::route('/'),
            'create' => CreateAutoTopupLog::route('/create'),
            'view' => ViewAutoTopupLog::route('/{record}'),
            'edit' => EditAutoTopupLog::route('/{record}/edit'),
        ];
    }
}
