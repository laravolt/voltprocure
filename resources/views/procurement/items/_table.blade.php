{{-- Expects $items: iterable of App\Models\Item. --}}
@if (count($items) === 0)
    <x-empty>Semua stok aman.</x-empty>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                <tr>
                    <th scope="col" class="px-5 py-2.5 font-medium">SKU</th>
                    <th scope="col" class="px-5 py-2.5 font-medium">Barang</th>
                    <th scope="col" class="hidden px-5 py-2.5 font-medium md:table-cell">Kategori</th>
                    <th scope="col" class="px-5 py-2.5 text-right font-medium">Stok</th>
                    <th scope="col" class="hidden px-5 py-2.5 font-medium sm:table-cell"><span class="sr-only">Terhadap titik pesan ulang</span></th>
                    <th scope="col" class="hidden px-5 py-2.5 text-right font-medium lg:table-cell">Harga standar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($items as $item)
                    @php
                        $low = $item->reorder_point > 0 && $item->stock_on_hand <= $item->reorder_point;
                        $fill = $item->reorder_point > 0 ? min(100, round($item->stock_on_hand / ($item->reorder_point * 2) * 100)) : 0;
                    @endphp
                    <tr class="text-zinc-800 dark:text-zinc-200">
                        <td class="px-5 py-2.5"><x-doc-no :number="$item->sku" :href="route('items.show', $item)" /></td>
                        <td class="max-w-64 truncate px-5 py-2.5">{{ $item->name }}</td>
                        <td class="hidden px-5 py-2.5 text-zinc-500 md:table-cell dark:text-zinc-400">{{ $item->category }}</td>
                        <td class="whitespace-nowrap px-5 py-2.5 text-right tabular-nums">
                            <span @class(['font-semibold text-red-700 dark:text-red-300' => $low])>{{ number_format($item->stock_on_hand, 0, ',', '.') }}</span>
                            <span class="text-zinc-500 dark:text-zinc-400">{{ $item->unit }}</span>
                        </td>
                        <td class="hidden w-40 px-5 py-2.5 sm:table-cell">
                            @if ($item->reorder_point > 0)
                                {{-- Bar spans 0 to twice the reorder point; the tick marks the reorder point. --}}
                                <div class="relative h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-800" title="Titik pesan ulang {{ $item->reorder_point }} {{ $item->unit }}">
                                    <div class="h-full rounded-full {{ $low ? 'bg-red-500' : 'bg-zinc-400 dark:bg-zinc-500' }}" style="width: {{ $fill }}%"></div>
                                    <div class="absolute -top-0.5 left-1/2 h-2.5 w-0.5 bg-zinc-900 dark:bg-zinc-100" aria-hidden="true"></div>
                                </div>
                                <span class="sr-only">{{ $low ? 'Di bawah' : 'Di atas' }} titik pesan ulang {{ $item->reorder_point }}</span>
                            @else
                                {{-- Bought per request, not kept in stock: a bar would read as full. --}}
                                <span class="whitespace-nowrap text-xs text-zinc-500 dark:text-zinc-400">Tidak distok</span>
                            @endif
                        </td>
                        <td class="hidden px-5 py-2.5 text-right lg:table-cell"><x-money :amount="$item->standard_cost" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
