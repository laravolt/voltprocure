@push('head')
    @livewireStyles
@endpush

@push('body')
    @livewireScripts
@endpush

<x-volt-app :title="__('Edit Profile')">
    <x-volt-panel title="{{ __('Edit Profile') }}" icon="user-edit">
        <livewire:my.profile-form />
    </x-volt-panel>
</x-volt-app>
