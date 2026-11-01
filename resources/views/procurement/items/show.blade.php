<x-workspace :title="$item->name">
    <div class="mx-auto max-w-5xl space-y-6 pb-6">
        <x-page-header :title="$item->name">
            <x-slot:meta><x-doc-no :number="$item->sku" /> · {{ $item->category }}</x-slot:meta>
            <x-slot:actions><x-button variant="secondary" :href="route('items.index')">Kembali ke stok</x-button></x-slot:actions>
        </x-page-header>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat label="Stok saat ini">{{ number_format($item->stock_on_hand, 0, ',', '.') }} <span class="text-base font-normal text-zinc-500">{{ $item->unit }}</span></x-stat>
            <x-stat label="Titik pesan ulang">{{ number_format($item->reorder_point, 0, ',', '.') }} <span class="text-base font-normal text-zinc-500">{{ $item->unit }}</span></x-stat>
            <x-stat label="Harga standar"><x-money :amount="$item->standard_cost" /></x-stat>
        </div>

        <x-card title="Riwayat mutasi" description="50 mutasi terakhir">
            @if ($movements->isEmpty())
                <x-empty>Belum ada mutasi untuk barang ini.</x-empty>
            @else
                <div class="overflow-x-auto">
                    <table class="basictable w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-5 py-2.5 font-medium">Tanggal</th>
                                <th scope="col" class="px-5 py-2.5 font-medium">Jenis</th>
                                <th scope="col" class="px-5 py-2.5 font-medium">Dokumen</th>
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">Jumlah</th>
                                <th scope="col" class="hidden px-5 py-2.5 font-medium sm:table-cell">Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 text-zinc-800 dark:divide-zinc-800 dark:text-zinc-200">
                            @foreach ($movements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-2.5 tabular-nums">{{ $movement->moved_at->translatedFormat('j M Y') }}</td>
                                    <td class="px-5 py-2.5">{{ $movement->type->label() }}</td>
                                    <td class="px-5 py-2.5">
                                        @if ($movement->source instanceof \App\Models\GoodsReceipt)
                                            <x-doc-no :number="$movement->source->number" :href="route('purchase-orders.show', $movement->source->purchase_order_id)" />
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-2.5 text-right font-medium tabular-nums {{ $movement->quantity >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                        {{ $movement->quantity >= 0 ? '+' : '−' }}{{ number_format(abs($movement->quantity), 0, ',', '.') }}
                                    </td>
                                    <td class="hidden px-5 py-2.5 text-zinc-500 sm:table-cell dark:text-zinc-400">{{ $movement->creator?->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-workspace>
