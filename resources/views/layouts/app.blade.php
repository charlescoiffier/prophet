{{-- Mise en page des composants Livewire pleine page : voir components/site-page. --}}
<x-site-page :title="$title ?? null" :description="$description ?? null">
    {{ $slot }}
</x-site-page>
