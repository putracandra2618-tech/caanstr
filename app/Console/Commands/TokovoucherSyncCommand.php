<?php

namespace App\Console\Commands;

use App\Services\TokovoucherService;
use Illuminate\Console\Command;

class TokovoucherSyncCommand extends Command
{
    protected $signature = 'tokovoucher:sync';

    protected $description = 'Sync products from Tokovoucher API';

    public function handle(TokovoucherService $tokovoucher): int
    {
        $this->info('Syncing products from Tokovoucher...');

        try {
            $result = $tokovoucher->syncProducts();

            $this->info('Sync completed:');
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
