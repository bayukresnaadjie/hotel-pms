@extends('layouts.app')
@section('title','Status Kamar')
@section('subtitle', $ringkasan['terisi'] . ' terisi · ' . $ringkasan['siap'] . ' siap jual · ' . $ringkasan['kotor'] . ' kotor · ' . $ringkasan['rusak'] . ' rusak')
@section('content')
@php $bisaUbah = auth()->user()->punyaRole('admin','frontdesk','housekeeping'); $bisaRusak = auth()->user()->punyaRole('admin','frontdesk'); @endphp

<div class="flex flex-wrap gap-3 mb-5 text-xs">
    <span class="flex items-center gap-1.5 bg-white border border-stone-200 rounded-full px-3 py-1.5"><span class="w-2.5 h-2.5 rounded-full bg-sky-400"></span>Terisi {{ $ringkasan['terisi'] }}</span>
    <span class="flex items-center gap-1.5 bg-white border border-stone-200 rounded-full px-3 py-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Siap jual {{ $ringkasan['siap'] }}</span>
    <span class="flex items-center gap-1.5 bg-white border border-stone-200 rounded-full px-3 py-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>Kotor {{ $ringkasan['kotor'] }}</span>
    <span class="flex items-center gap-1.5 bg-white border border-stone-200 rounded-full px-3 py-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>Dibersihkan {{ $ringkasan['dibersihkan'] }}</span>
    <span class="flex items-center gap-1.5 bg-white border border-stone-200 rounded-full px-3 py-1.5"><span class="w-2.5 h-2.5 rounded-full bg-stone-400"></span>Rusak {{ $ringkasan['rusak'] }}</span>
</div>

@foreach($perLantai as $lantai => $daftar)
<div class="mb-6">
    <h2 class="text-sm font-semibold text-stone-500 mb-2">Lantai {{ $lantai }}</h2>
    <div class="grid grid-cols-4 xl:grid-cols-6 gap-3">
        @foreach($daftar as $k)
        @php
            $tamuKini = $k->reservasiInHouse;
            $kelas = $tamuKini ? 'border-sky-300 bg-sky-50' : match($k->status_kamar) {
                'siap' => 'border-emerald-200 bg-emerald-50', 'kotor' => 'border-red-200 bg-red-50',
                'dibersihkan' => 'border-yellow-200 bg-yellow-50', default => 'border-stone-300 bg-stone-100',
            };
        @endphp
        <div class="rounded-xl border-2 {{ $kelas }} p-3">
            <div class="flex items-start justify-between"><span class="text-lg font-bold text-stone-900">{{ $k->nomor }}</span><span class="text-xs text-stone-400">{{ $k->tipeKamar->kode }}</span></div>
            @if($tamuKini)
                <div class="text-xs font-semibold text-sky-800 truncate mt-1" title="{{ $tamuKini->tamu->nama }}">{{ $tamuKini->tamu->nama }}</div>
                <div class="text-xs text-sky-700">out {{ $tamuKini->tanggal_keluar->translatedFormat('d M') }}</div>
            @else
                <div class="text-xs font-semibold mt-1 {{ $k->status_badge['class'] }} inline-block px-2 py-0.5 rounded-full">{{ $k->status_badge['label'] }}</div>
            @endif
            @if($k->catatan)<div class="text-xs text-stone-500 mt-1 truncate" title="{{ $k->catatan }}">{{ $k->catatan }}</div>@endif

            @if($bisaUbah)
            <details class="mt-2">
                <summary class="text-xs text-stone-400 hover:text-stone-700 cursor-pointer">Ubah status</summary>
                <form action="{{ route('kamar.status',$k) }}" method="POST" class="mt-1.5 space-y-1.5">@csrf @method('PATCH')
                    <select name="status_kamar" class="w-full border border-stone-200 rounded px-2 py-1 text-xs bg-white">
                        @foreach(['siap'=>'Siap','kotor'=>'Kotor','dibersihkan'=>'Dibersihkan'] as $v=>$l)<option value="{{ $v }}" {{ $k->status_kamar===$v?'selected':'' }}>{{ $l }}</option>@endforeach
                        @if($bisaRusak)<option value="rusak" {{ $k->status_kamar==='rusak'?'selected':'' }}>Rusak (OOO)</option>@endif
                    </select>
                    <input name="catatan" placeholder="Catatan" class="w-full border border-stone-200 rounded px-2 py-1 text-xs"/>
                    <button class="w-full bg-stone-800 text-white text-xs py-1 rounded">Simpan</button>
                </form>
            </details>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endforeach
@endsection
