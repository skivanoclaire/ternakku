@props(['q' => '', 'opsi' => [], 'benar' => 0, 'jawaban' => ''])
{{-- Kuis pilihan ganda interaktif (Alpine). Klik opsi -> tampil benar/salah + penjelasan. --}}
<div x-data="{ pilih: null, kunci: {{ (int) $benar }} }"
     class="bg-amber-50/60 border border-amber-200 rounded-2xl p-5 space-y-3">
    <p class="font-semibold text-brand-800 flex items-start gap-2"><span>❓ Kuis</span></p>
    <p class="text-brand-800">{{ $q }}</p>
    <div class="space-y-2">
        @foreach ($opsi as $i => $o)
            <button type="button" @click="pilih = {{ $i }}"
                class="w-full text-left px-4 py-2 rounded-lg border text-sm transition"
                :class="pilih === null
                    ? 'bg-white border-brand-200 hover:bg-brand-100'
                    : ({{ $i }} === kunci
                        ? 'bg-green-100 border-green-400 text-green-800 font-medium'
                        : (pilih === {{ $i }} ? 'bg-red-100 border-red-300 text-red-800' : 'bg-white border-brand-100 opacity-50'))">
                {{ chr(65 + $i) }}. {{ $o }}
            </button>
        @endforeach
    </div>
    <p x-show="pilih !== null" x-cloak class="text-sm rounded-lg px-3 py-2"
       :class="pilih === kunci ? 'bg-green-100 text-green-800' : 'bg-red-50 text-red-700'">
        <span x-text="pilih === kunci ? '✓ Benar! ' : '✗ Belum tepat. '"></span>{{ $jawaban }}
    </p>
</div>
