@php($input = 'block w-full rounded-lg border-zinc-300 text-base shadow-xs sm:text-sm focus:border-accent focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100')

<x-workspace :title="$po->number">
    <div class="mx-auto max-w-5xl space-y-6 pb-6">
        <x-page-header :title="(string) $po->supplier?->name">
            <x-slot:meta>
                <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <x-doc-no :number="$po->number" class="text-zinc-900 dark:text-zinc-100" />
                    <x-status :status="$po->status" />
                    @if ($po->isOverdue())
                        <span class="text-sm font-medium text-red-700 dark:text-red-300">Terlambat sejak {{ $po->expected_at?->translatedFormat('j M') }}</span>
                    @endif
                </span>
            </x-slot:meta>
        </x-page-header>

        @error('status')
            <p role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">{{ $message }}</p>
        @enderror

        <x-card>
            <dl class="grid gap-x-6 gap-y-4 p-5 text-sm sm:grid-cols-4">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">Dari permintaan</dt>
                    <dd class="mt-0.5 font-medium text-zinc-900 dark:text-zinc-100">
                        @if ($po->purchaseRequest)
                            <x-doc-no :number="$po->purchaseRequest->number" :href="route('purchase-requests.show', $po->purchaseRequest)" />
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div><dt class="text-zinc-500 dark:text-zinc-400">Terbit</dt><dd class="mt-0.5 font-medium text-zinc-900 tabular-nums dark:text-zinc-100">{{ $po->issued_at?->translatedFormat('j M Y') }} · {{ $po->buyer?->name }}</dd></div>
                <div><dt class="text-zinc-500 dark:text-zinc-400">Perkiraan tiba</dt><dd class="mt-0.5 font-medium text-zinc-900 tabular-nums dark:text-zinc-100">{{ $po->expected_at?->translatedFormat('j F Y') ?? '—' }}</dd></div>
                <div><dt class="text-zinc-500 dark:text-zinc-400">Total</dt><dd class="mt-0.5 font-medium text-zinc-900 dark:text-zinc-100"><x-money :amount="$po->total" /></dd></div>
            </dl>
        </x-card>

        @if ($canReceive)
            <form method="post" action="{{ route('purchase-orders.receipts.store', $po) }}" class="space-y-6">
                @csrf
        @endif

        <x-card title="Barang" :description="$canReceive ? 'Isi jumlah yang datang hari ini. Penerimaan sebagian boleh; sisanya tetap tercatat.' : null">
            @error('lines')
                <p class="px-5 pt-4 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
            @enderror
            <div class="overflow-x-auto">
                <table class="basictable w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-5 py-2.5 font-medium">Barang</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">Dipesan</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">Diterima</th>
                            <th scope="col" class="hidden px-5 py-2.5 text-right font-medium sm:table-cell">Harga satuan</th>
                            @if ($canReceive)
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">Terima sekarang</th>
                            @else
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">Subtotal</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-800 dark:divide-zinc-800 dark:text-zinc-200">
                        @foreach ($po->lines as $line)
                            <tr>
                                <td class="px-5 py-2.5">
                                    <p class="font-medium">{{ $line->item?->name }}</p>
                                    <p class="doc-no text-xs text-zinc-500 dark:text-zinc-400">{{ $line->item?->sku }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-2.5 text-right tabular-nums">{{ $line->quantity_ordered }} {{ $line->item?->unit }}</td>
                                <td class="whitespace-nowrap px-5 py-2.5 text-right tabular-nums">
                                    {{ $line->quantity_received }}
                                    @if ($line->outstandingQuantity() > 0 && $line->quantity_received > 0)
                                        <span class="text-xs text-amber-700 dark:text-amber-300">(kurang {{ $line->outstandingQuantity() }})</span>
                                    @endif
                                </td>
                                <td class="hidden px-5 py-2.5 text-right sm:table-cell"><x-money :amount="$line->unit_cost" /></td>
                                @if ($canReceive)
                                    <td class="px-5 py-2.5">
                                        @if ($line->outstandingQuantity() > 0)
                                            <label for="received-{{ $line->id }}" class="sr-only">Jumlah {{ $line->item?->name }} yang diterima</label>
                                            <input id="received-{{ $line->id }}" name="received[{{ $line->id }}]" type="number" inputmode="numeric" min="0" max="{{ $line->outstandingQuantity() }}"
                                                   value="{{ old('received.'.$line->id, $line->outstandingQuantity()) }}" class="{{ $input }} ml-auto w-24 text-right tabular-nums">
                                        @else
                                            <p class="text-right text-xs text-emerald-700 dark:text-emerald-300">Lengkap</p>
                                        @endif
                                    </td>
                                @else
                                    <td class="px-5 py-2.5 text-right"><x-money :amount="$line->quantity_ordered * $line->unit_cost" /></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($canReceive)
                <x-card>
                    <div class="flex flex-wrap items-end gap-4 p-5">
                        <x-field label="Catatan penerimaan" name="note" class="min-w-60 flex-1" hint="Misalnya nomor surat jalan.">
                            <input id="note" name="note" type="text" maxlength="255" value="{{ old('note') }}" class="{{ $input }}">
                        </x-field>
                        <x-button class="mb-6">Catat penerimaan</x-button>
                    </div>
                </x-card>
            </form>
        @endif

        <x-card title="Penerimaan barang" :description="$po->goodsReceipts->isEmpty() ? null : $po->goodsReceipts->count().' kali penerimaan'">
            @forelse ($po->goodsReceipts->sortByDesc('received_at') as $receipt)
                <div class="border-b border-zinc-100 px-5 py-3 text-sm last:border-b-0 dark:border-zinc-800">
                    <p class="flex flex-wrap items-center gap-x-3">
                        <x-doc-no :number="$receipt->number" class="font-medium text-zinc-900 dark:text-zinc-100" />
                        <span class="text-zinc-500 tabular-nums dark:text-zinc-400">{{ $receipt->received_at->translatedFormat('j M Y, H:i') }} · {{ $receipt->receiver?->name }}</span>
                    </p>
                    <p class="mt-1 text-zinc-700 dark:text-zinc-300">
                        {{ $receipt->lines->map(fn ($line) => $line->quantity.' '.$line->purchaseOrderLine?->item?->unit.' '.$line->purchaseOrderLine?->item?->name)->join(', ') }}
                    </p>
                    @if ($receipt->note)
                        <p class="mt-0.5 text-zinc-500 dark:text-zinc-400">{{ $receipt->note }}</p>
                    @endif
                </div>
            @empty
                <x-empty>Belum ada barang yang diterima.</x-empty>
            @endforelse
        </x-card>
    </div>
</x-workspace>
