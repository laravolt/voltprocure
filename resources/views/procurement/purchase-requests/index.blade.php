@php
    $options = ['' => 'Semua'];
    foreach (\App\Enums\PurchaseRequestStatus::cases() as $case) {
        $options[$case->value] = $case->label();
    }
@endphp

<x-workspace title="Permintaan barang">
    <div class="mx-auto max-w-7xl space-y-6 pb-6">
        <x-page-header title="Permintaan barang">
            <x-slot:meta>Permintaan diajukan pemohon, diputuskan manajer departemen, lalu dipesan oleh pengadaan.</x-slot:meta>
            @can(\App\Enums\Permission::PURCHASE_REQUEST_CREATE)
                <x-slot:actions><x-button :href="route('purchase-requests.create')">Buat permintaan</x-button></x-slot:actions>
            @endcan
        </x-page-header>

        <x-card>
            <div class="border-b border-zinc-200 px-5 dark:border-zinc-800">
                <x-tabs :current="$status" :options="$options" />
            </div>

            @if ($requests->isEmpty())
                <x-empty>Belum ada permintaan dengan status ini.</x-empty>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-5 py-2.5 font-medium">Nomor</th>
                                <th scope="col" class="px-5 py-2.5 font-medium">Keperluan</th>
                                <th scope="col" class="hidden px-5 py-2.5 font-medium md:table-cell">Pemohon</th>
                                <th scope="col" class="hidden px-5 py-2.5 font-medium lg:table-cell">Dibuat</th>
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">Estimasi</th>
                                <th scope="col" class="px-5 py-2.5 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 text-zinc-800 dark:divide-zinc-800 dark:text-zinc-200">
                            @foreach ($requests as $pr)
                                <tr>
                                    <td class="px-5 py-2.5"><x-doc-no :number="$pr->number" :href="route('purchase-requests.show', $pr)" /></td>
                                    <td class="max-w-72 truncate px-5 py-2.5">{{ $pr->purpose }}</td>
                                    <td class="hidden px-5 py-2.5 md:table-cell">
                                        {{ $pr->requester?->name }}
                                        <span class="text-zinc-500 dark:text-zinc-400">· {{ $pr->department?->code }}</span>
                                    </td>
                                    <td class="hidden whitespace-nowrap px-5 py-2.5 text-zinc-600 tabular-nums lg:table-cell dark:text-zinc-400">{{ $pr->created_at?->translatedFormat('j M Y') }}</td>
                                    <td class="px-5 py-2.5 text-right"><x-money :amount="$pr->estimatedTotal()" /></td>
                                    <td class="px-5 py-2.5"><x-status :status="$pr->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($requests->hasPages())
                <div class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-800">{{ $requests->links() }}</div>
            @endif
        </x-card>
    </div>
</x-workspace>
