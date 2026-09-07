<?php

namespace App\Filament\Resources\SyncedProducts\Pages;

use App\Filament\Resources\SyncedProducts\SyncedProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSyncedProduct extends CreateRecord
{
    protected static string $resource = SyncedProductResource::class;
}
