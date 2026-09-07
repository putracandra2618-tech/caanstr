<?php

namespace App\Filament\Resources\SyncedProducts\Pages;

use App\Filament\Resources\SyncedProducts\SyncedProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSyncedProducts extends ListRecords
{
    protected static string $resource = SyncedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
