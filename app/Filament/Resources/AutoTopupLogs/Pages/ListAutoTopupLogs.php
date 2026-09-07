<?php

namespace App\Filament\Resources\AutoTopupLogs\Pages;

use App\Filament\Resources\AutoTopupLogs\AutoTopupLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAutoTopupLogs extends ListRecords
{
    protected static string $resource = AutoTopupLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
