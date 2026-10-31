<x-workspace title="Stok barang">
    <div class="mx-auto max-w-7xl space-y-6 pb-6">
        <x-page-header title="Stok barang">
            <x-slot:meta>Stok bertambah otomatis setiap penerimaan barang dicatat.</x-slot:meta>
        </x-page-header>

        <x-card>
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-zinc-200 px-5 dark:border-zinc-800">
                <x-tabs param="stock" :current="$stock" :options="['' => 'Semua', 'low' => 'Perlu dipesan ulang']" />
                <form method="get" class="flex items-center gap-2 py-2" role="search">
                    @if ($stock !== '')
                        <input type="hidden" name="stock" value="{{ $stock }}">
                    @endif
                    <label for="q" class="sr-only">Cari barang</label>
                    <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nama atau SKU"
                           class="w-56 rounded-lg border-zinc-300 py-1.5 text-base sm:text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                </form>
            </div>
            @include('procurement.items._table', ['items' => $items])
            @if ($items->hasPages())
                <div class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-800">{{ $items->links() }}</div>
            @endif
        </x-card>
    </div>
</x-workspace>
