@php
    $options = ['' => 'Semua', 'open' => 'Belum diterima lengkap'];
    foreach ([\App\Enums\PurchaseOrderStatus::Issued, \App\Enums\PurchaseOrderStatus::PartiallyReceived, \App\Enums\PurchaseOrderStatus::Received] as $case) {
        $options[$case->value] = $case->label();
    }
@endphp

<x-workspace title="Pesanan pembelian">
    <div class="mx-auto max-w-7xl space-y-6 pb-6">
        <x-page-header title="Pesanan pembelian">
            <x-slot:meta>PO dibuat dari permintaan yang sudah disetujui, lalu diterima gudang secara bertahap.</x-slot:meta>
        </x-page-header>

        <x-card>
            <div class="border-b border-zinc-200 px-5 dark:border-zinc-800">
                <x-tabs :current="$status" :options="$options" />
            </div>

            @if ($orders->isEmpty())
                <x-empty>Belum ada pesanan dengan status ini.</x-empty>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-5 py-2.5 font-medium">Nomor</th>
                                <th scope="col" class="px-5 py-2.5 font-medium">Pemasok</th>
                                <th scope="col" class="hidden px-5 py-2.5 font-medium md:table-cell">Dari permintaan</th>
                                <th scope="col" class="hidden px-5 py-2.5 font-medium lg:table-cell">Perkiraan tiba</th>
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">Total</th>
                                <th scope="col" class="px-5 py-2.5 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 text-zinc-800 dark:divide-zinc-800 dark:text-zinc-200">
                            @foreach ($orders as $po)
                                <tr>
                                    <td class="px-5 py-2.5"><x-doc-no :number="$po->number" :href="route('purchase-orders.show', $po)" /></td>
                                    <td class="max-w-64 truncate px-5 py-2.5">{{ $po->supplier?->name }}</td>
                                    <td class="hidden px-5 py-2.5 md:table-cell">
                                        @if ($po->purchaseRequest)
                                            <x-doc-no :number="$po->purchaseRequest->number" class="text-zinc-600 dark:text-zinc-400" />
                                        @endif
                                    </td>
                                    <td class="hidden whitespace-nowrap px-5 py-2.5 tabular-nums lg:table-cell">
                                        <span @class(['font-medium text-red-700 dark:text-red-300' => $po->isOverdue(), 'text-zinc-600 dark:text-zinc-400' => ! $po->isOverdue()])>
                                            {{ $po->expected_at?->translatedFormat('j M Y') }}@if ($po->isOverdue()) · terlambat @endif
                                        </span>
                                    </td>
                                    <td class="px-5 py-2.5 text-right"><x-money :amount="$po->total" /></td>
                                    <td class="px-5 py-2.5"><x-status :status="$po->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($orders->hasPages())
                <div class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-800">{{ $orders->links() }}</div>
            @endif
        </x-card>
    </div>
</x-workspace>
