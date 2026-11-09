@extends('layouts.app')
@use('App\Support\Rp')
@section('title', $tamu->nama)
@section('subtitle', $tamu->telepon . ' · ' . $tamu->negara)
@section('header-action')<div class="flex gap-2"><a href="{{ route('reservasi.create',['tamu_id'=>$tamu->id]) }}" class="bg-amber-500 hover:bg-amber-600 text-stone-900 text-sm font-semibold px-4 py-2 rounded-lg">+ Reservasi</a><a href="{{ route('tamu.edit',$tamu) }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm px-4 py-2 rounded-lg">Ubah</a></div>@endsection
@section('content')
<div class="grid grid-cols-3 gap-5">
<div class="space-y-4">
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <div class="flex items-center gap-3 mb-4"><div class="w-12 h-12 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center text-lg font-bold">{{ $tamu->inisial }}</div>
        <div><div class="font-bold text-stone-900">{{ $tamu->nama }}</div><span class="text-xs px-2.5 py-0.5 rounded-full {{ $tamu->tipe_badge['class'] }}">{{ $tamu->tipe_badge['icon'] }} {{ $tamu->tipe_badge['label'] }}</span></div></div>
        <dl class="space-y-2 text-sm">
            <div><dt class="text-xs text-stone-400">Email</dt><dd>{{ $tamu->email ?? '—' }}</dd></div>
            <div><dt class="text-xs text-stone-400">Identitas</dt><dd>{{ strtoupper($tamu->jenis_identitas) }} {{ $tamu->nomor_identitas ?? '—' }}</dd></div>
            <div><dt class="text-xs text-stone-400">Asal</dt><dd>{{ $tamu->kota }}{{ $tamu->kota ? ', ' : '' }}{{ $tamu->negara }}</dd></div>
            <div><dt class="text-xs text-stone-400">Tanggal lahir</dt><dd>{{ $tamu->tanggal_lahir?->translatedFormat('d F Y') ?? '—' }}</dd></div>
        </dl>
        @if($tamu->catatan)<div class="mt-4 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 text-xs text-amber-900">{{ $tamu->catatan }}</div>@endif
    </div>
    <div class="grid grid-cols-3 gap-3 text-center">
        <div class="bg-white rounded-xl border border-stone-200 p-3"><div class="text-xs text-stone-400">Menginap</div><div class="text-xl font-bold">{{ $ringkasan['menginap'] }}×</div></div>
        <div class="bg-white rounded-xl border border-stone-200 p-3"><div class="text-xs text-stone-400">Malam</div><div class="text-xl font-bold">{{ $ringkasan['malam'] }}</div></div>
        <div class="bg-white rounded-xl border border-stone-200 p-3"><div class="text-xs text-stone-400">Belanja</div><div class="text-sm font-bold text-emerald-600 mt-1">{{ Rp::juta($ringkasan['belanja']) }}</div></div>
    </div>
</div>
<div class="col-span-2 bg-white rounded-xl border border-stone-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Riwayat Reservasi</h2></div>
    <table class="w-full text-sm"><thead class="bg-stone-50"><tr><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Booking</th><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Kamar</th><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Menginap</th><th class="text-right px-5 py-2.5 text-xs font-semibold text-stone-400">Tagihan</th><th class="text-center px-5 py-2.5 text-xs font-semibold text-stone-400">Status</th></tr></thead>
    <tbody class="divide-y divide-stone-50">
        @forelse($riwayat as $r)
        <tr class="hover:bg-stone-50"><td class="px-5 py-2.5"><a href="{{ route('reservasi.show',$r) }}" class="font-mono text-xs font-semibold text-amber-700 hover:underline">{{ $r->kode_booking }}</a></td>
        <td class="px-5 py-2.5 text-xs text-stone-600">{{ $r->tipeKamar->nama }}{{ $r->kamar ? ' · ' . $r->kamar->nomor : '' }}</td>
        <td class="px-5 py-2.5 text-xs text-stone-600">{{ $r->tanggal_masuk->translatedFormat('d M Y') }} · {{ $r->jumlah_malam }} malam</td>
        <td class="px-5 py-2.5 text-right text-xs font-medium">{{ $r->folio ? Rp::fmt($r->folio->total) : '—' }}</td>
        <td class="px-5 py-2.5 text-center"><span class="text-xs px-2 py-0.5 rounded-full {{ $r->status_badge['class'] }}">{{ $r->status_badge['label'] }}</span></td></tr>
        @empty<tr><td colspan="5" class="px-5 py-10 text-center text-sm text-stone-400">Belum ada reservasi</td></tr>@endforelse
    </tbody></table>
</div>
</div>
@endsection
