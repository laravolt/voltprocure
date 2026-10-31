@php($input = 'block w-full rounded-lg border-zinc-300 text-base shadow-xs sm:text-sm focus:border-accent focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100')

<x-workspace title="Buat permintaan barang">
    <div class="mx-auto max-w-4xl space-y-6 pb-6">
        <x-page-header title="Buat permintaan barang">
            <x-slot:meta>Nomor dokumen dibuat otomatis saat disimpan. Harga memakai harga standar; pengadaan menetapkan harga final di PO.</x-slot:meta>
        </x-page-header>

        <form method="post" action="{{ route('purchase-requests.store') }}" class="space-y-6">
            @csrf

            <x-card>
                <div class="grid gap-5 p-5 sm:grid-cols-3">
                    <x-field label="Keperluan" name="purpose" class="sm:col-span-2" hint="Contoh: Toner printer lantai 3 habis">
                        <input id="purpose" name="purpose" type="text" required maxlength="160" value="{{ old('purpose') }}"
                               class="{{ $input }}" @error('purpose') aria-invalid="true" aria-describedby="purpose-error" @enderror>
                    </x-field>
                    <x-field label="Dibutuhkan paling lambat" name="needed_by">
                        <input id="needed_by" name="needed_by" type="date" value="{{ old('needed_by') }}" min="{{ now()->toDateString() }}"
                               class="{{ $input }}" @error('needed_by') aria-invalid="true" aria-describedby="needed_by-error" @enderror>
                    </x-field>
                </div>
            </x-card>

            <x-card title="Barang" description="Isi baris yang diperlukan saja; baris kosong diabaikan.">
                @error('lines')
                    <p class="px-5 pt-4 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                @enderror
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @for ($row = 0; $row < $rows; $row++)
                        <div class="grid grid-cols-[1fr_7rem] items-start gap-3 px-5 py-3">
                            <div>
                                <label for="lines-{{ $row }}-item" class="sr-only">Barang baris {{ $row + 1 }}</label>
                                <select id="lines-{{ $row }}-item" name="lines[{{ $row }}][item_id]" class="{{ $input }}">
                                    <option value="">Pilih barang…</option>
                                    @foreach ($items->groupBy('category') as $category => $group)
                                        <optgroup label="{{ $category }}">
                                            @foreach ($group as $item)
                                                <option value="{{ $item->id }}" @selected(old("lines.$row.item_id") === $item->id)>
                                                    {{ $item->sku }} · {{ $item->name }} · Rp {{ number_format($item->standard_cost, 0, ',', '.') }}/{{ $item->unit }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error("lines.$row.item_id")
                                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="lines-{{ $row }}-quantity" class="sr-only">Jumlah baris {{ $row + 1 }}</label>
                                <input id="lines-{{ $row }}-quantity" name="lines[{{ $row }}][quantity]" type="number" inputmode="numeric" min="1"
                                       value="{{ old("lines.$row.quantity") }}" placeholder="Jumlah" class="{{ $input }} text-right tabular-nums">
                                @error("lines.$row.quantity")
                                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endfor
                </div>
            </x-card>

            <div class="flex flex-wrap justify-end gap-2">
                <x-button variant="secondary" :href="route('purchase-requests.index')">Batal</x-button>
                <x-button variant="secondary" name="intent" value="draft">Simpan sebagai draf</x-button>
                <x-button name="intent" value="submit">Simpan dan ajukan</x-button>
            </div>
        </form>
    </div>
</x-workspace>
