<x-workspace title="Pemasok">
    <div class="mx-auto max-w-7xl space-y-6 pb-6">
        <x-page-header title="Pemasok" />

        <x-card>
            <div class="overflow-x-auto">
                <table class="basictable w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-5 py-2.5 font-medium">Kode</th>
                            <th scope="col" class="px-5 py-2.5 font-medium">Pemasok</th>
                            <th scope="col" class="hidden px-5 py-2.5 font-medium md:table-cell">Kontak</th>
                            <th scope="col" class="hidden px-5 py-2.5 font-medium lg:table-cell">Pembayaran</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">PO terbuka</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">Total dipesan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-800 dark:divide-zinc-800 dark:text-zinc-200">
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td class="px-5 py-2.5"><x-doc-no :number="$supplier->code" /></td>
                                <td class="px-5 py-2.5">
                                    <p class="font-medium">{{ $supplier->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $supplier->city }}</p>
                                </td>
                                <td class="hidden px-5 py-2.5 md:table-cell">
                                    <p>{{ $supplier->contact_name }}</p>
                                    <p class="text-xs text-zinc-500 tabular-nums dark:text-zinc-400">{{ $supplier->phone }}</p>
                                </td>
                                <td class="hidden px-5 py-2.5 text-zinc-600 lg:table-cell dark:text-zinc-400">{{ $supplier->payment_terms }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums">{{ $supplier->open_orders_count }}</td>
                                <td class="px-5 py-2.5 text-right"><x-money :amount="(int) $supplier->ordered_total" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-workspace>
