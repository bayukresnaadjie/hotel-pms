@extends('layouts.app')
@section('title','Tarif Musiman')
@section('subtitle','Pengali harga untuk rentang tanggal tertentu')
@section('content')
<div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-900 mb-4">Bila beberapa tarif musiman berlaku pada malam yang sama, <b>pengali tertinggi</b> yang dipakai. Pengali akhir pekan ({{ config('hotel.weekend_pengali') }}×) tetap dikalikan di atasnya. 1,50 berarti harga naik 50%, 0,90 berarti diskon 10%.</div>
<form action="{{ route('master.tarif.store') }}" method="POST" class="bg-white rounded-xl border border-stone-200 p-5 mb-5 grid grid-cols-6 gap-3 items-end">@csrf
    <div class="col-span-6 text-sm font-semibold">➕ Tambah Tarif Musiman</div>
    <div class="col-span-2"><label class="block text-xs text-stone-500 mb-1">Nama</label><input name="nama" required placeholder="mis. Libur Lebaran" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Mulai</label><input type="date" name="tanggal_mulai" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Selesai</label><input type="date" name="tanggal_selesai" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Pengali</label><input type="number" name="pengali" step="0.05" min="0.5" max="5" value="1.25" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Tipe kamar</label><select name="tipe_kamar_id" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"><option value="">Semua tipe</option>@foreach($tipeList as $t)<option value="{{ $t->id }}">{{ $t->nama }}</option>@endforeach</select></div>
    <div class="col-span-6"><button class="bg-amber-500 hover:bg-amber-600 text-stone-900 font-semibold text-sm px-5 py-2 rounded-lg">Simpan Tarif</button></div>
</form>
<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <table class="w-full text-sm"><thead class="bg-stone-50 border-b border-stone-200"><tr>
        <th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Nama</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Periode</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Tipe Kamar</th>
        <th class="text-center px-5 py-3 text-xs font-semibold text-stone-400">Pengali</th><th class="text-center px-5 py-3 text-xs font-semibold text-stone-400">Status</th><th class="px-5 py-3"></th></tr></thead>
    <tbody class="divide-y divide-stone-100">
        @forelse($tarif as $t)
        @php $berjalan = $t->tanggal_mulai->lte(today()) && $t->tanggal_selesai->gte(today()); $lewat = $t->tanggal_selesai->lt(today()); @endphp
        <tr class="hover:bg-stone-50 {{ $lewat ? 'opacity-50' : '' }}">
            <td class="px-5 py-3 font-medium text-stone-900">{{ $t->nama }}</td>
            <td class="px-5 py-3 text-xs text-stone-600">{{ $t->tanggal_mulai->translatedFormat('d M Y') }} – {{ $t->tanggal_selesai->translatedFormat('d M Y') }}</td>
            <td class="px-5 py-3 text-xs text-stone-600">{{ $t->tipeKamar?->nama ?? 'Semua tipe' }}</td>
            <td class="px-5 py-3 text-center"><span class="font-bold {{ (float)$t->pengali>=1 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format((float)$t->pengali, 2, ',', '.') }}×</span></td>
            <td class="px-5 py-3 text-center"><span class="text-xs px-2.5 py-1 rounded-full {{ $berjalan ? 'bg-emerald-100 text-emerald-700' : ($lewat ? 'bg-stone-100 text-stone-500' : 'bg-blue-100 text-blue-700') }}">{{ $berjalan ? 'Berjalan' : ($lewat ? 'Selesai' : 'Akan datang') }}</span></td>
            <td class="px-5 py-3 text-right"><form action="{{ route('master.tarif.destroy',$t) }}" method="POST" onsubmit="return confirm('Hapus tarif musiman ini?')">@csrf @method('DELETE')<button class="text-xs text-red-600 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-lg">Hapus</button></form></td>
        </tr>
        @empty<tr><td colspan="6" class="px-5 py-10 text-center text-sm text-stone-400">Belum ada tarif musiman</td></tr>@endforelse
    </tbody></table>
</div>
@endsection
