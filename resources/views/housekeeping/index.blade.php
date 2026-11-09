@extends('layouts.app')
@section('title','Housekeeping')
@section('subtitle', today()->translatedFormat('l, d F Y'))
@section('header-action')<a href="{{ route('housekeeping.kerusakan') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm px-4 py-2 rounded-lg">🔧 Laporan Kerusakan</a>@endsection
@section('content')
<div class="grid grid-cols-5 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Menunggu</div><div class="text-2xl font-bold text-stone-900">{{ $stat['menunggu'] }}</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Dikerjakan</div><div class="text-2xl font-bold text-yellow-600">{{ $stat['dikerjakan'] }}</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Selesai Hari Ini</div><div class="text-2xl font-bold text-emerald-600">{{ $stat['selesai'] }}</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Kamar Kotor</div><div class="text-2xl font-bold {{ $stat['kotor']>0?'text-red-500':'text-stone-300' }}">{{ $stat['kotor'] }}</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Kamar Rusak</div><div class="text-2xl font-bold text-stone-700">{{ $stat['rusak'] }}</div></div>
</div>

@if($kamarTanpaTugas->count() > 0)
<div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 mb-5">
    <div class="text-sm font-semibold text-red-800 mb-2">Kamar kotor yang belum punya tugas ({{ $kamarTanpaTugas->count() }})</div>
    <div class="flex flex-wrap gap-2">
        @foreach($kamarTanpaTugas as $k)
        <form action="{{ route('housekeeping.tugas.store') }}" method="POST">@csrf<input type="hidden" name="kamar_id" value="{{ $k->id }}"/>
            <button class="text-xs bg-white border border-red-200 hover:bg-red-100 text-red-700 rounded-lg px-3 py-1.5">Kamar {{ $k->nomor }} · buat tugas</button></form>
        @endforeach
    </div>
</div>
@endif

<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Tugas Hari Ini</h2></div>
    <table class="w-full text-sm"><thead class="bg-stone-50"><tr>
        <th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Kamar</th><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Jenis</th>
        <th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Petugas</th><th class="text-center px-5 py-2.5 text-xs font-semibold text-stone-400">Status</th>
        <th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Catatan</th><th class="px-5 py-2.5"></th></tr></thead>
    <tbody class="divide-y divide-stone-50">
        @forelse($tugas as $t)
        <tr class="hover:bg-stone-50">
            <td class="px-5 py-3"><div class="font-bold text-stone-900">{{ $t->kamar->nomor }}</div><div class="text-xs text-stone-400">{{ $t->kamar->tipeKamar->nama }} · <span class="{{ $t->kamar->status_badge['class'] }} px-1.5 rounded">{{ $t->kamar->status_badge['label'] }}</span></div></td>
            <td class="px-5 py-3 text-xs text-stone-600">{{ $t->tipe_label }}</td>
            <td class="px-5 py-3">
                <form action="{{ route('housekeeping.tugaskan',$t) }}" method="POST" class="flex gap-1">@csrf @method('PATCH')
                    <select name="ditugaskan_ke" class="border border-stone-200 rounded px-2 py-1 text-xs">@foreach($petugas as $p)<option value="{{ $p->id }}" {{ $t->ditugaskan_ke==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select>
                    <button class="text-xs bg-stone-100 hover:bg-stone-200 px-2 rounded">OK</button>
                </form>
            </td>
            <td class="px-5 py-3 text-center"><span class="text-xs px-2.5 py-1 rounded-full {{ $t->status_badge['class'] }}">{{ $t->status_badge['label'] }}</span></td>
            <td class="px-5 py-3 text-xs text-stone-500">{{ $t->catatan }}</td>
            <td class="px-5 py-3 text-right whitespace-nowrap">
                @if($t->status==='menunggu')<form action="{{ route('housekeeping.mulai',$t) }}" method="POST" class="inline">@csrf @method('PATCH')<button class="text-xs bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1.5 rounded-lg">Mulai</button></form>@endif
                @if($t->status!=='selesai')<form action="{{ route('housekeeping.selesai',$t) }}" method="POST" class="inline">@csrf @method('PATCH')<button class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg">Selesai</button></form>@endif
            </td>
        </tr>
        @empty<tr><td colspan="6" class="px-5 py-12 text-center text-sm text-stone-400">Belum ada tugas hari ini</td></tr>@endforelse
    </tbody></table>
</div>
@endsection
