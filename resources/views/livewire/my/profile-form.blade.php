<div>
    @if (session()->has('success'))
        <x-volt-alert variant="success" class="mb-4">
            {{ session('success') }}
        </x-volt-alert>
    @endif

    <div wire:offline>
        <x-volt-alert variant="warning" class="mb-4">
            {{ __('Koneksi terputus. Perubahan belum dapat dikirim; periksa jaringan lalu coba lagi.') }}
        </x-volt-alert>
    </div>

    <form
        method="POST"
        action="{{ route('my::profile.update') }}"
        wire:submit="save"
        wire:loading.attr="aria-busy"
        wire:target="save"
        data-profile-form
    >
        @csrf
        @method('PUT')

        <fieldset
            wire:loading.attr="disabled"
            wire:target="save"
            class="min-w-0 space-y-5 border-0 p-0"
            data-profile-fields
        >
            <x-volt-input
                id="profile-name"
                name="name"
                wire:model="name"
                :value="$name"
                :label="__('Name')"
                :variant="$errors->has('name') ? 'error' : 'default'"
                :error="$errors->first('name') ?: null"
                autocomplete="name"
                required
            />

            <x-volt-input
                id="profile-email"
                name="email"
                type="email"
                :value="$email"
                :label="__('Email')"
                autocomplete="email"
                readonly
            />

            <x-volt-select
                id="timezone"
                wire:model="timezone"
                :label="__('Timezone')"
                :placeholder="null"
                :variant="$errors->has('timezone') ? 'error' : 'default'"
                :error="$errors->first('timezone') ?: null"
                required
            >
                @foreach ($this->timezones as $value => $label)
                    <option value="{{ $value }}" @selected($timezone === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </x-volt-select>

            <div class="flex justify-end">
                <x-volt-button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    data-profile-save
                >
                    <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                    <span
                        wire:loading.flex
                        wire:target="save"
                        role="status"
                        class="items-center gap-2"
                        data-profile-save-loading
                    >
                        <span
                            class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent"
                            aria-hidden="true"
                        ></span>
                        {{ __('Saving...') }}
                    </span>
                </x-volt-button>
            </div>

            <p
                wire:loading.delay
                wire:target="save"
                role="status"
                aria-live="polite"
                class="text-right text-xs text-gray-500 dark:text-neutral-400"
            >
                {{ __('Profile is being saved. Please wait.') }}
            </p>
        </fieldset>
    </form>
</div>
