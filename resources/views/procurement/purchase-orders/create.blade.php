@php($input = 'block w-full rounded-lg border-zinc-300 text-base shadow-xs sm:text-sm focus:border-accent focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100')

<x-workspace title="Buat pesanan pembelian">
    <div class="mx-auto max-w-4xl space-y-6 pb-6">
        <x-page-header title="Buat pesanan pembelian">
            <x-slot:meta>
                Dari <x-doc-no :number="$pr->number" :href="route('purchase-requests.show', $pr)" /> · {{ $pr->purpose }} · {{ $pr->department?->name }}
            </x-slot:meta>
        </x-page-header>

        <form method="post" action="{{ route('purchase-orders.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="purchase_request_id" value="{{ $pr->id }}">

            @error('status')
                <p role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">{{ $message }}</p>
            @enderror

            <x-card>
                <div class="grid gap-5 p-5 sm:grid-cols-3">
                    <x-field label="Pemasok" name="supplier_id" class="sm:col-span-2">
                        <select id="supplier_id" name="supplier_id" required class="{{ $input }}" @error('supplier_id') aria-invalid="true" aria-describedby="supplier_id-error" @enderror>
                            <option value="">Pilih pemasok…</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') === $supplier->id)>{{ $supplier->name }} · {{ $supplier->city }} · {{ $supplier->payment_terms }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field label="Perkiraan tiba" name="expected_at">
                        <input id="expected_at" name="expected_at" type="date" required min="{{ now()->toDateString() }}"
                               value="{{ old('expected_at', $pr->needed_by?->toDateString() ?? now()->addWeek()->toDateString()) }}"
                               class="{{ $input }}" @error('expected_at') aria-invalid="true" aria-describedby="expected_at-error" @enderror>
                    </x-field>
                </div>
            </x-card>

            <x-card title="Harga yang disepakati" description="Terisi harga estimasi; ubah sesuai penawaran pemasok.">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-5 py-2.5 font-medium">Barang</th>
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">Jumlah</th>
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">Harga satuan (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 text-zinc-800 dark:divide-zinc-800 dark:text-zinc-200">
                            @foreach ($pr->lines as $line)
                                <tr>
                                    <td class="px-5 py-2.5">
                                        <p class="font-medium">{{ $line->item?->name }}</p>
                                        <p class="doc-no text-xs text-zinc-500 dark:text-zinc-400">{{ $line->item?->sku }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-2.5 text-right tabular-nums">{{ $line->approved_quantity }} {{ $line->item?->unit }}</td>
                                    <td class="px-5 py-2.5">
                                        <label for="unit-cost-{{ $line->id }}" class="sr-only">Harga satuan {{ $line->item?->name }}</label>
                                        <input id="unit-cost-{{ $line->id }}" name="unit_cost[{{ $line->id }}]" type="number" inputmode="numeric" min="0" step="1"
                                               value="{{ old('unit_cost.'.$line->id, $line->estimated_unit_cost) }}" class="{{ $input }} ml-auto w-36 text-right tabular-nums">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            <div class="flex flex-wrap justify-end gap-2">
                <x-button variant="secondary" :href="route('purchase-requests.show', $pr)">Batal</x-button>
                <x-button>Terbitkan PO</x-button>
            </div>
        </form>
    </div>
</x-workspace>
