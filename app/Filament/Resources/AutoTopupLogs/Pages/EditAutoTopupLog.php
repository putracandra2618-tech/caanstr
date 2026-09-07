<?php

namespace App\Filament\Resources\AutoTopupLogs\Pages;

use App\Filament\Resources\AutoTopupLogs\AutoTopupLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAutoTopupLog extends EditRecord
{
    protected static string $resource = AutoTopupLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
