@extends('layouts.app')
@section('title','Beranda')

@section('content')
<div class="min-h-screen">

    {{-- Navbar --}}
    <nav class="fixed top-0 inset-x-0 z-50 backdrop-blur bg-white/70 border-b border-brand-100">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 font-bold text-brand-700 text-lg">
                <span class="w-9 h-9 rounded-xl bg-brand-600 text-white grid place-items-center animate-float">🐄</span>
                TernakKu
            </a>
            <div class="flex items-center gap-3">
                <a href="#estimasi" class="text-sm font-medium text-brand-700 hover:text-brand-900">Coba Estimasi</a>
                <a href="{{ route('login') }}" class="text-sm font-medium text-brand-700 hover:text-brand-900">Masuk</a>
                <a href="{{ route('register') }}" class="text-sm font-semibold px-4 py-2 rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition shadow-lg shadow-brand-600/20">Daftar Peternak</a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <header class="relative bg-mesh animate-gradient text-white pt-32 pb-24 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-72 h-72 bg-brand-400/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-0 -left-10 w-72 h-72 bg-brand-300/20 rounded-full blur-3xl animate-float" style="animation-delay:2s"></div>
        <div class="max-w-6xl mx-auto px-6 relative">
            <p class="animate-fade-up inline-block px-3 py-1 rounded-full bg-white/15 text-sm mb-5">Marketplace ternak berbasis data science</p>
            <h1 class="animate-fade-up delay-1 text-4xl md:text-6xl font-extrabold leading-tight max-w-3xl">
                Tahu bobot &amp; harga wajar ternak<br>tanpa timbangan.
            </h1>
            <p class="animate-fade-up delay-2 mt-6 text-brand-50/90 max-w-xl text-lg">
                Estimasi bobot hidup dari ukuran tubuh memakai model machine learning, melawan asimetri informasi antara peternak dan tengkulak.
            </p>
            <div class="animate-fade-up delay-3 mt-8 flex gap-3">
                <a href="#estimasi" class="px-6 py-3 rounded-xl bg-white text-brand-700 font-semibold hover:scale-105 transition shadow-xl">Coba sekarang →</a>
                <a href="{{ route('login') }}" class="px-6 py-3 rounded-xl border border-white/40 font-semibold hover:bg-white/10 transition">Masuk Admin</a>
            </div>
        </div>
    </header>

    {{-- Estimasi widget --}}
    <section id="estimasi" class="max-w-6xl mx-auto px-6 -mt-14 relative z-10 pb-20">
        <div x-data="estimator()" class="grid md:grid-cols-2 gap-6 bg-white rounded-3xl shadow-2xl shadow-brand-900/10 p-8 border border-brand-100 animate-pop">
            <div>
                <h2 class="text-2xl font-bold text-brand-800">Estimasi Bobot Sapi</h2>
                <p class="text-brand-600/80 text-sm mt-1 mb-6">Masukkan ukuran tubuh (cm). Lingkar dada wajib.</p>
                <form @submit.prevent="hitung" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Lingkar dada (cm) *</label>
                        <input x-model.number="ld" type="number" step="0.1" required
                               class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Panjang badan</label>
                            <input x-model.number="pb" type="number" step="0.1"
                                   class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tinggi gumba</label>
                            <input x-model.number="tg" type="number" step="0.1"
                                   class="w-full rounded-xl border-brand-200 focus:ring-brand-500 focus:border-brand-500 bg-brand-50/50 px-4 py-2.5">
                        </div>
                    </div>
                    <button type="submit" :disabled="loading"
                            class="w-full py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition disabled:opacity-60">
                        <span x-show="!loading">Hitung estimasi</span>
                        <span x-show="loading">Menghitung…</span>
                    </button>
                </form>
            </div>
            <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 text-white p-8 grid place-items-center text-center">
                <template x-if="!hasil && !error">
                    <p class="text-brand-100/80">Hasil estimasi akan muncul di sini.</p>
                </template>
                <div x-show="error" x-cloak class="text-red-100" x-text="error"></div>
                <div x-show="hasil" x-cloak class="animate-pop">
                    <p class="text-brand-100 text-sm">Estimasi bobot</p>
                    <p class="text-6xl font-extrabold my-2"><span x-text="hasil?.bobot_estimasi_kg"></span><span class="text-2xl"> kg</span></p>
                    <p class="text-brand-100/90 text-sm">rentang wajar
                        <span x-text="hasil?.p10"></span>–<span x-text="hasil?.p90"></span> kg</p>
                    <p class="mt-3 text-xs text-brand-200/70" x-text="'model: '+(hasil?.model_ver||'')"></p>
                </div>
            </div>
        </div>
    </section>

    <footer class="border-t border-brand-100 py-8 text-center text-sm text-brand-600/70">
        TernakKu · ternakku.kaltaraprov.web.id · Modul 1 (estimasi bobot) aktif
    </footer>
</div>

<script>
function estimator(){
    return {
        ld:null, pb:null, tg:null, loading:false, hasil:null, error:null,
        async hitung(){
            this.loading=true; this.error=null; this.hasil=null;
            try{
                const res = await fetch('{{ route('estimasi.bobot') }}', {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
                    body: JSON.stringify({lingkar_dada_cm:this.ld, panjang_badan_cm:this.pb, tinggi_gumba_cm:this.tg})
                });
                const data = await res.json();
                if(!res.ok || data.error){ this.error = data.error || 'Gagal menghitung. Coba lagi.'; }
                else { this.hasil = data; }
            }catch(e){ this.error='Tidak bisa terhubung ke server.'; }
            this.loading=false;
        }
    }
}
</script>
@endsection
