<?php

namespace App\Filament\Resources\SyncedProducts\Pages;

use App\Filament\Resources\SyncedProducts\SyncedProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSyncedProduct extends ViewRecord
{
    protected static string $resource = SyncedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
