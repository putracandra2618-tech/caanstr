<?php

namespace App\Console\Commands;

use App\Services\DigiflazzService;
use Illuminate\Console\Command;

class DigiflazzSyncCommand extends Command
{
    protected $signature = 'digiflazz:sync';
    protected $description = 'Sync products from DigiFlazz API';

    public function handle(DigiflazzService $digiflazz): int
    {
        $this->info('Syncing products from DigiFlazz...');

        try {
            $result = $digiflazz->syncProducts();

            $this->info("Sync completed:");
            $this->line("  Created: {$result['created']}");
            $this->line("  Updated: {$result['updated']}");
            $this->line("  Skipped: {$result['skipped']}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
