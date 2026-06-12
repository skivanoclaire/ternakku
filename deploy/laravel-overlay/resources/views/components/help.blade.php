@props(['title' => 'Penjelasan', 'open' => false])
{{-- Panel penjelasan yang bisa dibuka-tutup (alat belajar). Pakai: <x-help title="...">isi</x-help> --}}
<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="bg-brand-50/70 border border-brand-100 rounded-2xl overflow-hidden">
    <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between px-5 py-3 text-left hover:bg-brand-100/50 transition">
        <span class="font-semibold text-brand-800 flex items-center gap-2 text-sm">
            <span>💡</span> {{ $title }}
        </span>
        <span class="text-brand-500 text-lg leading-none" x-text="open ? '–' : '+'"></span>
    </button>
    <div x-show="open" x-transition class="px-5 pb-4 text-sm text-brand-700 leading-relaxed space-y-2">
        {{ $slot }}
    </div>
</div>
