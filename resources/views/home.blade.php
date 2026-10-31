@php
    $hour = $now->setTimezone($user->timezone ?: config('app.timezone'))->hour;
    $greeting = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $peak = max(1, max(array_column($weeks, 'total')));
    $peakIndex = array_search($peak, array_column($weeks, 'total'), true);
    $lastIndex = count($weeks) - 1;
    $periodTotal = array_sum(array_column($weeks, 'total'));
    $change = $spend['previous'] > 0 ? ($spend['current'] - $spend['previous']) / $spend['previous'] * 100 : null;
@endphp

<x-workspace title="Ringkasan">
    <div class="mx-auto max-w-7xl space-y-6 pb-6">
        <x-page-header :eyebrow="$now->translatedFormat('l, j F Y')" :title="$greeting.', '.str($user->name)->before(' ')">
            @can(\App\Enums\Permission::PURCHASE_REQUEST_CREATE)
                <x-slot:actions>
                    <x-button :href="route('purchase-requests.create')">Buat permintaan</x-button>
                </x-slot:actions>
            @endcan
        </x-page-header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat label="Menunggu persetujuan" :href="route('purchase-requests.index', ['status' => 'submitted'])">
                {{ $pending['count'] }}
                <x-slot:note>senilai <x-money :amount="$pending['value']" compact /></x-slot:note>
            </x-stat>
            <x-stat label="PO belum diterima lengkap" :href="route('purchase-orders.index', ['status' => 'open'])">
                {{ $openOrders['count'] }}
                <x-slot:note>
                    @if ($openOrders['overdue'] > 0)
                        <span class="font-medium text-red-700 dark:text-red-300">{{ $openOrders['overdue'] }} terlambat</span> ·
                    @endif
                    sisa <x-money :amount="$openOrders['value']" compact />
                </x-slot:note>
            </x-stat>
            <x-stat label="Stok di bawah titik pesan ulang" :href="route('items.index', ['stock' => 'low'])">
                {{ $lowStock->count() }}
                <x-slot:note>dari {{ \App\Models\Item::query()->where('is_active', true)->count() }} barang aktif</x-slot:note>
            </x-stat>
            <x-stat label="Belanja bulan ini">
                <x-money :amount="$spend['current']" compact />
                <x-slot:note>
                    @if ($change === null)
                        belum ada pembanding
                    @else
                        {{ $change >= 0 ? 'naik' : 'turun' }} {{ number_format(abs($change), 0, ',', '.') }}% dari periode yang sama bulan lalu
                    @endif
                </x-slot:note>
            </x-stat>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- The one rich moment: where the money went, week by week. Bars top out at 85% to leave room for their labels. --}}
            <x-card class="lg:col-span-3" title="Nilai PO terbit per minggu" :description="'8 minggu terakhir · total '.number_format($periodTotal, 0, ',', '.')">
                <figure class="px-5 pt-6 pb-4">
                    <div class="relative flex h-52 items-end gap-2 border-b border-zinc-300 dark:border-zinc-700" role="img"
                         aria-label="Grafik kolom nilai PO per minggu. Puncak {{ number_format($weeks[$peakIndex]['total'], 0, ',', '.') }} rupiah pada minggu {{ $weeks[$peakIndex]['start']->translatedFormat('j M') }}.">
                        @foreach ($weeks as $index => $week)
                            @php($height = $week['total'] > 0 ? max(2, round($week['total'] / $peak * 85)) : 0)
                            <div class="group relative flex h-full flex-1 flex-col items-center justify-end outline-none" tabindex="0"
                                 aria-label="Minggu {{ $week['start']->translatedFormat('j M') }}: {{ number_format($week['total'], 0, ',', '.') }} rupiah, {{ $week['orders'] }} PO">
                                @if ($index === $peakIndex || $index === $lastIndex)
                                    <span class="mb-1 text-xs font-medium text-zinc-700 dark:text-zinc-300"><x-money :amount="$week['total']" compact /></span>
                                @endif
                                <div class="w-full max-w-6 rounded-t-[4px] bg-accent transition-opacity duration-150 group-hover:opacity-80 group-focus-visible:opacity-80 dark:bg-indigo-500" style="height: {{ $height }}%"></div>
                                <div class="pointer-events-none absolute bottom-full z-10 mb-2 hidden w-max rounded-lg bg-zinc-900 px-3 py-2 text-xs text-white shadow-lg group-hover:block group-focus-visible:block dark:bg-zinc-100 dark:text-zinc-900" role="tooltip">
                                    <p class="font-medium">{{ $week['start']->translatedFormat('j M') }} – {{ $week['start']->endOfWeek()->translatedFormat('j M') }}</p>
                                    <p class="tabular-nums"><x-money :amount="$week['total']" /> · {{ $week['orders'] }} PO</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-2" aria-hidden="true">
                        @foreach ($weeks as $week)
                            <span class="flex-1 text-center text-xs text-zinc-500 tabular-nums dark:text-zinc-400">{{ $week['start']->translatedFormat('j M') }}</span>
                        @endforeach
                    </div>
                    <details class="mt-4 text-sm">
                        <summary class="cursor-pointer text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200">Lihat sebagai tabel</summary>
                        <table class="mt-2 w-full text-left">
                            <thead class="text-zinc-500 dark:text-zinc-400">
                                <tr><th class="py-1 font-medium">Minggu mulai</th><th class="py-1 text-right font-medium">PO</th><th class="py-1 text-right font-medium">Nilai</th></tr>
                            </thead>
                            <tbody class="text-zinc-800 dark:text-zinc-200">
                                @foreach ($weeks as $week)
                                    <tr class="border-t border-zinc-100 dark:border-zinc-800">
                                        <td class="py-1">{{ $week['start']->translatedFormat('j F Y') }}</td>
                                        <td class="py-1 text-right tabular-nums">{{ $week['orders'] }}</td>
                                        <td class="py-1 text-right"><x-money :amount="$week['total']" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                </figure>
            </x-card>

            <x-card class="lg:col-span-2" title="Pekerjaan Anda" :description="count($work) > 0 ? count($work).' dokumen menunggu Anda' : null">
                @forelse ($work as $task)
                    <a href="{{ $task['url'] }}" class="flex items-start gap-3 border-b border-zinc-100 px-5 py-3 last:border-b-0 transition-colors duration-150 hover:bg-zinc-50 focus-visible:bg-zinc-50 focus-visible:outline-none dark:border-zinc-800 dark:hover:bg-zinc-800/60 dark:focus-visible:bg-zinc-800/60">
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-x-2 text-sm">
                                <x-doc-no :number="$task['number']" class="text-zinc-500 dark:text-zinc-400" />
                                @if ($task['urgent'])
                                    <span class="text-xs font-medium text-red-700 dark:text-red-300">Perlu segera</span>
                                @endif
                            </p>
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $task['title'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $task['meta'] }}</p>
                        </div>
                        <span class="mt-1 shrink-0 text-sm font-medium text-accent dark:text-indigo-300">{{ $task['action'] }} →</span>
                    </a>
                @empty
                    <x-empty>Tidak ada dokumen yang menunggu Anda. Meja Anda rapi.</x-empty>
                @endforelse
            </x-card>
        </div>

        <x-card title="Stok perlu dipesan ulang" description="Barang aktif dengan stok di bawah atau sama dengan titik pesan ulang">
            <x-slot:action>
                <a href="{{ route('items.index', ['stock' => 'low']) }}" class="text-sm font-medium text-accent hover:underline dark:text-indigo-300">Semua stok</a>
            </x-slot:action>
            @include('procurement.items._table', ['items' => $lowStock->take(8)])
        </x-card>
    </div>
</x-workspace>
