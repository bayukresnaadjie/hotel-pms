@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Reservasi')
@section('subtitle', $reservasi->total() . ' reservasi')
@section('header-action')<a href="{{ route('reservasi.create') }}" class="bg-amber-500 hover:bg-amber-600 text-stone-900 text-sm font-semibold px-4 py-2 rounded-lg transition">+ Reservasi Baru</a>@endsection
@section('content')
<form method="GET" class="bg-white rounded-xl border border-stone-200 p-4 mb-4 grid grid-cols-6 gap-3 items-end">
    <div class="col-span-2"><label class="block text-xs text-stone-500 mb-1">Cari</label><input name="q" value="{{ request('q') }}" placeholder="Kode booking, nama, atau telepon tamu" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Status</label>
        <select name="status" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"><option value="">Semua</option>
        @foreach(['dikonfirmasi'=>'Dikonfirmasi','check_in'=>'In-House','check_out'=>'Check-Out','dibatalkan'=>'Dibatalkan','no_show'=>'No-Show'] as $v=>$l)<option value="{{ $v }}" {{ request('status')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-stone-500 mb-1">Sumber</label>
        <select name="sumber" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"><option value="">Semua</option>
        @foreach(['walk_in'=>'Walk-in','telepon'=>'Telepon','website'=>'Website','ota'=>'OTA'] as $v=>$l)<option value="{{ $v }}" {{ request('sumber')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-stone-500 mb-1">Masuk dari</label><input type="date" name="dari" value="{{ request('dari') }}" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div class="flex gap-2"><div class="flex-1"><label class="block text-xs text-stone-500 mb-1">Sampai</label><input type="date" name="sampai" value="{{ request('sampai') }}" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div><button class="bg-stone-800 text-white text-sm px-4 py-2 rounded-lg">Cari</button></div>
</form>
<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <table class="w-full text-sm"><thead class="bg-stone-50 border-b border-stone-200"><tr>
        <th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Booking</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Tamu</th>
        <th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Kamar</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Menginap</th>
        <th class="text-right px-5 py-3 text-xs font-semibold text-stone-400">Total Kamar</th><th class="text-center px-5 py-3 text-xs font-semibold text-stone-400">Status</th><th class="px-5 py-3"></th></tr></thead>
    <tbody class="divide-y divide-stone-100">
        @forelse($reservasi as $r)
        <tr class="hover:bg-stone-50">
            <td class="px-5 py-3"><div class="font-mono text-xs font-semibold text-amber-700">{{ $r->kode_booking }}</div><div class="text-xs text-stone-400">{{ $r->sumber_label }}</div></td>
            <td class="px-5 py-3"><div class="font-medium text-stone-900">{{ $r->tamu->nama }} @if($r->tamu->tipe==='vip')⭐@endif</div><div class="text-xs text-stone-400">{{ $r->tamu->telepon }}</div></td>
            <td class="px-5 py-3 text-xs text-stone-600">{{ $r->tipeKamar->nama }}@if($r->kamar)<div class="font-semibold text-stone-800">Kamar {{ $r->kamar->nomor }}</div>@endif</td>
            <td class="px-5 py-3 text-xs text-stone-600">{{ $r->tanggal_masuk->translatedFormat('d M') }} → {{ $r->tanggal_keluar->translatedFormat('d M Y') }}<div class="text-stone-400">{{ $r->jumlah_malam }} malam</div></td>
            <td class="px-5 py-3 text-right font-medium">{{ Rp::fmt($r->total_kamar) }}</td>
            <td class="px-5 py-3 text-center"><span class="text-xs px-2.5 py-1 rounded-full {{ $r->status_badge['class'] }}">{{ $r->status_badge['label'] }}</span></td>
            <td class="px-5 py-3 text-right"><a href="{{ route('reservasi.show',$r) }}" class="text-xs text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1 rounded-lg">Detail</a></td>
        </tr>
        @empty<tr><td colspan="7" class="px-5 py-12 text-center text-sm text-stone-400">Tidak ada reservasi</td></tr>@endforelse
    </tbody></table>
    @if($reservasi->hasPages())<div class="px-5 py-3 border-t">{{ $reservasi->links() }}</div>@endif
</div>
@endsection
