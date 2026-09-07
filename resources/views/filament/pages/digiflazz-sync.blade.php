<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Sinkronisasi produk dari DigiFlazz
        </x-slot>
        <x-slot name="description">
            Tarik price list DigiFlazz untuk dibuat atau diperbarui sebagai produk di toko.
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Produk</div>
                <div class="mt-1 text-2xl font-bold">{{ number_format($this->totalProducts) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">Produk Tersinkron</div>
                <div class="mt-1 text-2xl font-bold">{{ number_format($this->totalSynced) }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">Sync Terakhir</div>
                <div class="mt-1 text-2xl font-bold">{{ $this->lastSync }}</div>
            </x-filament::section>
        </div>
    </x-filament::section>
</x-filament-panels::page>