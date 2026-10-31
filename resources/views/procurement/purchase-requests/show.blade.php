@php($input = 'block w-full rounded-lg border-zinc-300 text-base shadow-xs sm:text-sm focus:border-accent focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100')

<x-workspace :title="$pr->number">
    <div class="mx-auto max-w-5xl space-y-6 pb-6">
        <x-page-header :title="$pr->purpose">
            <x-slot:meta>
                <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <x-doc-no :number="$pr->number" class="text-zinc-900 dark:text-zinc-100" />
                    <x-status :status="$pr->status" />
                </span>
            </x-slot:meta>
            <x-slot:actions>
                @if ($canSubmit)
                    <form method="post" action="{{ route('purchase-requests.submit', $pr) }}">
                        @csrf
                        <x-button>Ajukan ke manajer</x-button>
                    </form>
                @endif
                @if ($canOrder)
                    <x-button :href="route('purchase-orders.create', ['request' => $pr->id])">Buat pesanan pembelian</x-button>
                @endif
                @if ($pr->purchaseOrder)
                    <x-button variant="secondary" :href="route('purchase-orders.show', $pr->purchaseOrder)">
                        Lihat <span class="doc-no">{{ $pr->purchaseOrder->number }}</span>
                    </x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        @error('status')
            <p role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">{{ $message }}</p>
        @enderror

        <x-card>
            <dl class="grid gap-x-6 gap-y-4 p-5 text-sm sm:grid-cols-4">
                <div><dt class="text-zinc-500 dark:text-zinc-400">Pemohon</dt><dd class="mt-0.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $pr->requester?->name }}</dd></div>
                <div><dt class="text-zinc-500 dark:text-zinc-400">Departemen</dt><dd class="mt-0.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $pr->department?->name }}</dd></div>
                <div><dt class="text-zinc-500 dark:text-zinc-400">Dibutuhkan</dt><dd class="mt-0.5 font-medium text-zinc-900 tabular-nums dark:text-zinc-100">{{ $pr->needed_by?->translatedFormat('j F Y') ?? '—' }}</dd></div>
                <div><dt class="text-zinc-500 dark:text-zinc-400">Estimasi total</dt><dd class="mt-0.5 font-medium text-zinc-900 dark:text-zinc-100"><x-money :amount="$pr->estimatedTotal()" /></dd></div>
            </dl>
        </x-card>

        @if ($canDecide)
            <form method="post" action="{{ route('purchase-requests.decision', $pr) }}" class="space-y-6">
                @csrf
        @endif

        <x-card title="Barang" :description="$canDecide ? 'Kurangi jumlah disetujui bila perlu; isi 0 untuk mencoret barang.' : null">
            @error('lines')
                <p class="px-5 pt-4 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
            @enderror
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-5 py-2.5 font-medium">Barang</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">Diminta</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">Disetujui</th>
                            <th scope="col" class="hidden px-5 py-2.5 text-right font-medium sm:table-cell">Harga satuan</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-800 dark:divide-zinc-800 dark:text-zinc-200">
                        @foreach ($pr->lines as $line)
                            <tr>
                                <td class="px-5 py-2.5">
                                    <p class="font-medium">{{ $line->item?->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><span class="doc-no">{{ $line->item?->sku }}</span> · stok {{ $line->item?->stock_on_hand }} {{ $line->item?->unit }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-2.5 text-right tabular-nums">{{ $line->quantity }} {{ $line->item?->unit }}</td>
                                <td class="whitespace-nowrap px-5 py-2.5 text-right tabular-nums">
                                    @if ($canDecide)
                                        <label for="approved-{{ $line->id }}" class="sr-only">Jumlah disetujui untuk {{ $line->item?->name }}</label>
                                        <input id="approved-{{ $line->id }}" name="approved[{{ $line->id }}]" type="number" inputmode="numeric" min="0" max="{{ $line->quantity }}"
                                               value="{{ old('approved.'.$line->id, $line->quantity) }}" class="{{ $input }} ml-auto w-24 text-right tabular-nums">
                                    @else
                                        {{ $line->approved_quantity ?? '—' }}
                                    @endif
                                </td>
                                <td class="hidden px-5 py-2.5 text-right sm:table-cell"><x-money :amount="$line->estimated_unit_cost" /></td>
                                <td class="px-5 py-2.5 text-right"><x-money :amount="($line->approved_quantity ?? $line->quantity) * $line->estimated_unit_cost" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($canDecide)
                <x-card title="Keputusan">
                    <div class="space-y-4 p-5">
                        <x-field label="Catatan" name="note" hint="Wajib diisi bila menolak.">
                            <textarea id="note" name="note" rows="3" maxlength="500" class="{{ $input }}" @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ old('note') }}</textarea>
                        </x-field>
                        <div class="flex flex-wrap justify-end gap-2">
                            <x-button variant="danger" name="decision" value="reject">Tolak</x-button>
                            <x-button name="decision" value="approve">Setujui</x-button>
                        </div>
                    </div>
                </x-card>
            </form>
        @endif

        <x-card title="Riwayat">
            <ol class="space-y-3 p-5 text-sm">
                <li class="flex gap-3">
                    <span class="mt-1.5 size-2 shrink-0 rounded-full bg-zinc-400" aria-hidden="true"></span>
                    <p class="text-zinc-700 dark:text-zinc-300">Dibuat oleh {{ $pr->requester?->name }} <span class="text-zinc-500 tabular-nums dark:text-zinc-400">· {{ $pr->created_at?->translatedFormat('j M Y, H:i') }}</span></p>
                </li>
                @if ($pr->submitted_at)
                    <li class="flex gap-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>
                        <p class="text-zinc-700 dark:text-zinc-300">Diajukan ke {{ $pr->department?->manager?->name ?? 'manajer departemen' }} <span class="text-zinc-500 tabular-nums dark:text-zinc-400">· {{ $pr->submitted_at->translatedFormat('j M Y, H:i') }}</span></p>
                    </li>
                @endif
                @if ($pr->decided_at)
                    <li class="flex gap-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $pr->status === \App\Enums\PurchaseRequestStatus::Rejected ? 'bg-red-500' : 'bg-accent' }}" aria-hidden="true"></span>
                        <div>
                            <p class="text-zinc-700 dark:text-zinc-300">{{ $pr->status === \App\Enums\PurchaseRequestStatus::Rejected ? 'Ditolak' : 'Disetujui' }} oleh {{ $pr->decider?->name }} <span class="text-zinc-500 tabular-nums dark:text-zinc-400">· {{ $pr->decided_at->translatedFormat('j M Y, H:i') }}</span></p>
                            @if ($pr->decision_note)
                                <p class="mt-1 text-zinc-600 dark:text-zinc-400">“{{ $pr->decision_note }}”</p>
                            @endif
                        </div>
                    </li>
                @endif
                @if ($pr->purchaseOrder)
                    <li class="flex gap-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                        <p class="text-zinc-700 dark:text-zinc-300">Dipesan lewat <x-doc-no :number="$pr->purchaseOrder->number" :href="route('purchase-orders.show', $pr->purchaseOrder)" /> <span class="text-zinc-500 tabular-nums dark:text-zinc-400">· {{ $pr->purchaseOrder->issued_at?->translatedFormat('j M Y, H:i') }}</span></p>
                    </li>
                @endif
            </ol>
        </x-card>
    </div>
</x-workspace>
