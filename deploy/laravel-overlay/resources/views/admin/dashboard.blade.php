@extends('layouts.admin', ['title' => 'Dashboard Admin'])
@section('heading', 'Dashboard Admin')

@section('page')
    <x-help title="Membaca dashboard ini">
        <p>Empat kartu atas = ukuran data & aktivitas. Baris berikutnya: <b>status service ML</b> (hijau = Python aktif &
        model termuat), <b>model aktif</b> (yang melayani peternak), dan <b>tren akurasi</b> (MAPE tiap eksperimen menurut
        waktu — turun = platform makin akurat seiring data/metode membaik). Tabel bawah = <b>audit trail</b>: jejak siapa
        melakukan apa (validasi, latih, promosi, login) demi akuntabilitas.</p>
    </x-help>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([
            ['Pengguna', $stats['users'] ?? 0, '👥'],
            ['Ternak', $stats['ternak'] ?? 0, '🐄'],
            ['Pengukuran', $stats['pengukuran'] ?? 0, '📏'],
            ['Entri Audit', $stats['audits'] ?? 0, '🛡️'],
        ] as $i => $card)
            <div class="bg-white rounded-2xl p-5 border border-brand-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition animate-fade-up" style="animation-delay:{{ $i*80 }}ms">
                <div class="text-2xl">{{ $card[2] }}</div>
                <p class="text-3xl font-extrabold text-brand-800 mt-2">{{ number_format($card[1]) }}</p>
                <p class="text-sm text-brand-500">{{ $card[0] }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.latih') }}" class="px-5 py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition shadow-lg shadow-brand-600/20">⚙️ Latih model baru</a>
        <a href="{{ route('admin.leaderboard') }}" class="px-5 py-3 rounded-xl border border-brand-200 text-brand-700 font-semibold hover:bg-brand-100 transition">🏆 Lihat leaderboard</a>
        <a href="{{ route('admin.eda') }}" class="px-5 py-3 rounded-xl border border-brand-200 text-brand-700 font-semibold hover:bg-brand-100 transition">🔎 Eksplorasi data</a>
    </div>

    {{-- Notebook Colab --}}
    <div class="bg-white rounded-2xl border border-brand-100 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="text-3xl">📓</div>
        <div class="flex-1">
            <p class="font-bold text-brand-800">Notebook Colab — eksperimen ML (kode lengkap berkomentar per baris)</p>
            <p class="text-sm text-brand-500">Jalankan & pelajari pipeline model langsung di browser, tanpa server. Pas untuk belajar tiap baris kodenya.</p>
        </div>
        <a href="https://colab.research.google.com/drive/1oZMlZb7GrEFPCKJNXRGm6AMwDyaKbKXb?usp=sharing"
           target="_blank" rel="noopener"
           class="shrink-0 px-5 py-3 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/20">
            Buka di Google Colab ↗
        </a>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-brand-100 shadow-sm">
            <p class="text-xs text-brand-500">Status service ML</p>
            @php $up = ($mlStatus['status'] ?? '') === 'ok'; @endphp
            <p class="mt-2 flex items-center gap-2 font-bold {{ $up ? 'text-green-600' : 'text-red-600' }}">
                <span class="w-2.5 h-2.5 rounded-full {{ $up ? 'bg-green-500' : 'bg-red-500' }}"></span>
                {{ $up ? 'Aktif' : 'Tidak terhubung' }}
            </p>
            <p class="text-xs text-brand-400 mt-1">model termuat: {{ ($mlStatus['model_ada'] ?? false) ? 'ya' : 'belum' }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-brand-100 shadow-sm">
            <p class="text-xs text-brand-500">Model aktif</p>
            @if ($modelAktif)
                <p class="mt-2 font-bold text-brand-800">{{ $modelAktif->method_label ?? $modelAktif->method }}</p>
                <p class="text-xs text-brand-500">MAPE {{ $modelAktif->mape }}% · {{ $modelAktif->model_ver }}</p>
            @else
                <p class="mt-2 text-brand-400">Belum ada — <a href="{{ route('admin.latih') }}" class="text-brand-600 hover:underline">latih & promosikan</a></p>
            @endif
        </div>
        <div class="bg-white rounded-2xl p-5 border border-brand-100 shadow-sm">
            <p class="text-xs text-brand-500 mb-1">Tren akurasi (MAPE per eksperimen)</p>
            <canvas id="tren" height="80"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-brand-100 shadow-sm animate-fade-up delay-2">
        <div class="px-6 py-4 border-b border-brand-100 flex items-center justify-between">
            <h2 class="font-bold text-brand-800">Audit Trail Terbaru</h2>
            <span class="text-xs px-2.5 py-1 rounded-full bg-brand-100 text-brand-700">50 terakhir</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-brand-500 bg-brand-50/60">
                    <tr>
                        <th class="px-6 py-3 font-medium">Waktu</th>
                        <th class="px-6 py-3 font-medium">Pengguna</th>
                        <th class="px-6 py-3 font-medium">Event</th>
                        <th class="px-6 py-3 font-medium">Objek</th>
                        <th class="px-6 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-50">
                    @forelse ($auditTerbaru as $a)
                        <tr class="hover:bg-brand-50/50">
                            <td class="px-6 py-3 whitespace-nowrap text-brand-600">{{ $a->created_at?->format('d/m H:i') }}</td>
                            <td class="px-6 py-3">{{ $a->user?->name ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    @class([
                                        'bg-green-100 text-green-700' => in_array($a->event,['created','login']),
                                        'bg-amber-100 text-amber-700' => $a->event==='updated',
                                        'bg-red-100 text-red-700' => $a->event==='deleted',
                                        'bg-brand-100 text-brand-700' => !in_array($a->event,['created','updated','deleted','login']),
                                    ])">{{ $a->event }}</span>
                            </td>
                            <td class="px-6 py-3 text-brand-600">{{ class_basename($a->auditable_type) }}{{ $a->auditable_id ? ' #'.$a->auditable_id : '' }}</td>
                            <td class="px-6 py-3 text-brand-500">{{ $a->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-brand-400">Belum ada aktivitas tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const tren = @json($tren);
        if (tren.length) {
            new Chart(document.getElementById('tren'), {
                type: 'line',
                data: { labels: tren.map(t=>t.label), datasets: [{ data: tren.map(t=>t.mape),
                    borderColor:'#16a34a', backgroundColor:'rgba(22,163,74,.1)', fill:true, tension:.3, pointRadius:2 }] },
                options: { plugins:{legend:{display:false}}, scales:{y:{title:{display:true,text:'MAPE %'}}} }
            });
        }
    </script>
@endsection
