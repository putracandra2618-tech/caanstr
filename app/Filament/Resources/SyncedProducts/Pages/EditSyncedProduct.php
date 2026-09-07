<?php

namespace App\Filament\Resources\SyncedProducts\Pages;

use App\Filament\Resources\SyncedProducts\SyncedProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSyncedProduct extends EditRecord
{
    protected static string $resource = SyncedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
