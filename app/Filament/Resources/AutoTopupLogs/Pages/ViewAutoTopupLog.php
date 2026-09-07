<?php

namespace App\Filament\Resources\AutoTopupLogs\Pages;

use App\Filament\Resources\AutoTopupLogs\AutoTopupLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAutoTopupLog extends ViewRecord
{
    protected static string $resource = AutoTopupLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
