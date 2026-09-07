<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\SyncedProduct;
use App\Services\DigiflazzService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Throwable;
use UnitEnum;

class DigiflazzSync extends Page
{
    protected string $view = 'filament.pages.digiflazz-sync';

    protected static ?string $title = 'Sinkronisasi DigiFlazz';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 6;

    public int $totalProducts;

    public int $totalSynced;

    public string $lastSync;

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('balance')
                ->label('Cek Saldo')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('gray')
                ->action(fn () => $this->checkBalance()),
            Action::make('sync')
                ->label('Sync Produk')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi produk dari DigiFlazz?')
                ->modalDescription('Proses ini akan membuat/memperbarui produk dan kategori dari price list DigiFlazz. Mungkin berlangsung beberapa menit.')
                ->modalSubmitActionLabel('Ya, sync sekarang')
                ->action(fn () => $this->syncProducts()),
        ];
    }

    protected function syncProducts(): void
    {
        try {
            $result = app(DigiflazzService::class)->syncProducts();

            Cache::forever('digiflazz_last_sync', now()->toDateTimeString());
            $this->refreshStats();

            Notification::make()
                ->title('Sinkronisasi berhasil')
                ->body("Dibuat: {$result['created']} | Diperbarui: {$result['updated']} | Dilewati: {$result['skipped']}")
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Sinkronisasi gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function checkBalance(): void
    {
        $balance = app(DigiflazzService::class)->getBalance();

        if ($balance === null) {
            Notification::make()
                ->title('Gagal mengambil saldo DigiFlazz')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Saldo DigiFlazz')
            ->body('Rp '.number_format($balance, 0, ',', '.'))
            ->success()
            ->send();
    }

    protected function refreshStats(): void
    {
        $this->totalProducts = Product::query()->count();
        $this->totalSynced = SyncedProduct::query()->count();
        $this->lastSync = Cache::get('digiflazz_last_sync') ?? 'Belum pernah sinkron';
    }
}
