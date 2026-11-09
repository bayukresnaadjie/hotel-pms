@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Laporan Kinerja Hotel')
@section('subtitle', $dari->translatedFormat('d M Y') . ' – ' . $sampai->translatedFormat('d M Y') . ' · ' . $ringkas['hari'] . ' hari · ' . $jual . ' kamar dijual')
@section('content')
@php
$labelSumber = ['walk_in'=>'Walk-in','telepon'=>'Telepon','website'=>'Website','ota'=>'OTA (Traveloka/Booking)'];
$step = max(1, intdiv($harian->count(), 10));
@endphp

<form method="GET" class="bg-white rounded-xl border border-stone-200 p-4 mb-5 flex items-end gap-3">
    <div><label class="block text-xs text-stone-500 mb-1">Dari</label><input type="date" name="dari" value="{{ $dari->toDateString() }}" class="border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Sampai</label><input type="date" name="sampai" value="{{ $sampai->toDateString() }}" class="border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <button class="bg-stone-800 text-white text-sm px-5 py-2 rounded-lg">Tampilkan</button>
    <span class="text-xs text-stone-400 ml-2">Dihitung dari biaya kamar yang sudah diposting night audit. Maksimal 120 hari.</span>
</form>

<div class="grid grid-cols-6 gap-4 mb-5">
    <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl p-4 text-white col-span-2"><div class="text-xs text-amber-100 mb-1">Pendapatan Kamar</div><div class="text-2xl font-bold">{{ Rp::fmt($ringkas['pendapatan_kamar']) }}</div><div class="text-xs text-amber-100 mt-1">{{ $ringkas['malam_terjual'] }} malam kamar terjual</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Okupansi</div><div class="text-2xl font-bold text-stone-900">{{ $ringkas['okupansi'] }}%</div><div class="text-xs text-stone-400">rata-rata periode</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1" title="Average Daily Rate">ADR</div><div class="text-lg font-bold text-stone-900 mt-1">{{ Rp::fmt($ringkas['adr']) }}</div><div class="text-xs text-stone-400">tarif rata-rata / kamar</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1" title="Revenue Per Available Room">RevPAR</div><div class="text-lg font-bold text-stone-900 mt-1">{{ Rp::fmt($ringkas['revpar']) }}</div><div class="text-xs text-stone-400">pendapatan / kamar tersedia</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Pendapatan Lain</div><div class="text-lg font-bold text-emerald-600 mt-1">{{ Rp::fmt($ringkas['pendapatan_lain']) }}</div><div class="text-xs text-stone-400">diskon {{ Rp::fmt($ringkas['diskon']) }}</div></div>
</div>

<div class="bg-white rounded-xl border border-stone-200 p-5 mb-5">
    <h2 class="text-sm font-semibold mb-4">📈 Okupansi Harian</h2>
    <div class="flex items-end gap-px h-36">
        @foreach($harian as $h)
        <div class="flex-1 flex flex-col items-center justify-end h-full min-w-0" title="{{ $h['tanggal']->translatedFormat('D, d M') }}: {{ $h['okupansi'] }}% ({{ $h['terjual'] }} kamar) · {{ Rp::fmt($h['pendapatan']) }}">
            <div class="w-full rounded-t {{ in_array($h['tanggal']->dayOfWeek, config('hotel.weekend_days'), true) ? 'bg-amber-500' : 'bg-stone-400' }}" style="height:{{ max(2, min(100, $h['okupansi'])) }}%"></div>
            <span class="text-stone-400 mt-1" style="font-size:8px;height:10px">{{ $loop->index % $step === 0 ? $h['tanggal']->format('d') : '' }}</span>
        </div>
        @endforeach
    </div>
    <div class="flex gap-4 mt-2 text-xs text-stone-400"><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-500"></span>Akhir pekan</span><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-stone-400"></span>Hari biasa</span></div>
</div>

<div class="grid grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">🛏️ Per Tipe Kamar</h2></div>
        <table class="w-full text-sm"><thead class="bg-stone-50"><tr><th class="text-left px-4 py-2 text-xs font-semibold text-stone-400">Tipe</th><th class="text-right px-4 py-2 text-xs font-semibold text-stone-400">Malam</th><th class="text-right px-4 py-2 text-xs font-semibold text-stone-400">Pendapatan</th></tr></thead>
        <tbody class="divide-y divide-stone-50">@forelse($perTipe as $t)<tr><td class="px-4 py-2.5 font-medium">{{ $t->nama }}<div class="text-xs text-stone-400">ADR {{ Rp::fmt($t->malam > 0 ? $t->pendapatan / $t->malam : 0) }}</div></td><td class="px-4 py-2.5 text-right">{{ $t->malam }}</td><td class="px-4 py-2.5 text-right font-medium">{{ Rp::juta($t->pendapatan) }}</td></tr>
        @empty<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-stone-400">Belum ada data</td></tr>@endforelse</tbody></table>
    </div>
    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">📲 Sumber Reservasi</h2></div>
        <table class="w-full text-sm"><thead class="bg-stone-50"><tr><th class="text-left px-4 py-2 text-xs font-semibold text-stone-400">Sumber</th><th class="text-right px-4 py-2 text-xs font-semibold text-stone-400">Tamu</th><th class="text-right px-4 py-2 text-xs font-semibold text-stone-400">Nilai</th></tr></thead>
        <tbody class="divide-y divide-stone-50">@forelse($perSumber as $s)<tr><td class="px-4 py-2.5 font-medium">{{ $labelSumber[$s->sumber] ?? $s->sumber }}</td><td class="px-4 py-2.5 text-right">{{ $s->total }}</td><td class="px-4 py-2.5 text-right font-medium">{{ Rp::juta($s->nilai) }}</td></tr>
        @empty<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-stone-400">Belum ada data</td></tr>@endforelse</tbody></table>
    </div>
    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">🍽️ Layanan Terlaris</h2></div>
        <table class="w-full text-sm"><thead class="bg-stone-50"><tr><th class="text-left px-4 py-2 text-xs font-semibold text-stone-400">Layanan</th><th class="text-right px-4 py-2 text-xs font-semibold text-stone-400">Qty</th><th class="text-right px-4 py-2 text-xs font-semibold text-stone-400">Pendapatan</th></tr></thead>
        <tbody class="divide-y divide-stone-50">@forelse($perLayanan as $l)<tr><td class="px-4 py-2.5 font-medium">{{ $l->nama }}</td><td class="px-4 py-2.5 text-right">{{ (int) $l->qty }}</td><td class="px-4 py-2.5 text-right font-medium">{{ Rp::fmt($l->pendapatan) }}</td></tr>
        @empty<tr><td colspan="3" class="px-4 py-8 text-center text-sm text-stone-400">Belum ada data</td></tr>@endforelse</tbody></table>
    </div>
</div>

<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">🔮 Proyeksi 14 Malam ke Depan</h2><p class="text-xs text-stone-400">Dari reservasi aktif yang sudah masuk.</p></div>
    <table class="w-full text-sm"><thead class="bg-stone-50"><tr><th class="text-left px-5 py-2 text-xs font-semibold text-stone-400">Tanggal</th><th class="text-center px-5 py-2 text-xs font-semibold text-stone-400">Kamar Terisi</th><th class="px-5 py-2 text-xs font-semibold text-stone-400 w-1/2">Okupansi</th><th class="text-center px-5 py-2 text-xs font-semibold text-stone-400">Kedatangan</th></tr></thead>
    <tbody class="divide-y divide-stone-50">
        @foreach($proyeksi as $p)
        <tr class="{{ in_array($p['tanggal']->dayOfWeek, config('hotel.weekend_days'), true) ? 'bg-amber-50/40' : '' }}"><td class="px-5 py-2 font-medium">{{ $p['tanggal']->translatedFormat('D, d M') }}</td><td class="px-5 py-2 text-center">{{ $p['terisi'] }}</td>
        <td class="px-5 py-2"><div class="flex items-center gap-2"><div class="flex-1 bg-stone-100 rounded-full h-2"><div class="h-2 rounded-full {{ $p['okupansi']>=80?'bg-red-400':'bg-amber-500' }}" style="width:{{ min(100,$p['okupansi']) }}%"></div></div><span class="text-xs font-semibold w-10 text-right">{{ $p['okupansi'] }}%</span></div></td>
        <td class="px-5 py-2 text-center">{{ $p['kedatangan'] }}</td></tr>
        @endforeach
    </tbody></table>
</div>
@endsection
