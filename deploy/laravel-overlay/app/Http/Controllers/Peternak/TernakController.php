<?php

namespace App\Http\Controllers\Peternak;

use App\Http\Controllers\Controller;
use App\Models\Ternak;
use Illuminate\Http\Request;

/**
 * CRUD ternak milik peternak. ABAC: setiap aksi dibatasi kepemilikan
 * (user_id) — peternak hanya melihat/mengubah ternaknya sendiri.
 */
class TernakController extends Controller
{
    public function index(Request $request)
    {
        $ternak = Ternak::with('pengukuranTerakhir')
            ->where('user_id', $request->user()->id)
            ->latest()->get();

        return view('peternak.index', ['ternak' => $ternak]);
    }

    public function create()
    {
        return view('peternak.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jenis'   => ['required', 'in:sapi,kambing'],
            'ras'     => ['nullable', 'string', 'max:50'],
            'kelamin' => ['nullable', 'in:jantan,betina'],
            'umur_estimasi_bulan' => ['nullable', 'integer', 'min:0', 'max:600'],
        ]);
        $data['user_id'] = $request->user()->id;
        $data['wilayah_id'] = $request->user()->wilayah_id;

        $ternak = Ternak::create($data);
        return redirect()->route('ternak.show', $ternak)->with('ok', 'Ternak ditambahkan.');
    }

    public function show(Request $request, Ternak $ternak)
    {
        abort_unless($ternak->user_id === $request->user()->id, 403);
        $ternak->load('pengukuran');
        return view('peternak.show', ['ternak' => $ternak]);
    }
}
