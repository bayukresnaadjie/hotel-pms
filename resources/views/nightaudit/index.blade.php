@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Night Audit')
@section('subtitle', 'Tutup hari ' . $hariIni->translatedFormat('l, d F Y'))
@section('content')
@php $bisaReservasi = auth()->user()->punyaRole('admin','frontdesk'); @endphp
<div class="grid grid-cols-3 gap-5 mb-5">
<div class="col-span-2 space-y-4">
    @if($sudahAudit)
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
        <div class="text-sm font-semibold text-emerald-800 mb-3">✅ Night audit {{ $hariIni->translatedFormat('d F Y') }} sudah dijalankan oleh {{ $sudahAudit->dijalankanOleh?->name ?? '-' }}</div>
        <div class="grid grid-cols-4 gap-3 text-center">
            <div class="bg-white rounded-lg p-3"><div class="text-xs text-stone-400">In-house</div><div class="text-xl font-bold">{{ $sudahAudit->jumlah_in_house }}</div></div>
            <div class="bg-white rounded-lg p-3"><div class="text-xs text-stone-400">Diposting</div><div class="text-xl font-bold">{{ $sudahAudit->jumlah_diposting }}</div></div>
            <div class="bg-white rounded-lg p-3"><div class="text-xs text-stone-400">Okupansi</div><div class="text-xl font-bold">{{ $sudahAudit->okupansi }}%</div></div>
            <div class="bg-white rounded-lg p-3"><div class="text-xs text-stone-400">Pendapatan kamar</div><div class="text-sm font-bold mt-1">{{ Rp::fmt($sudahAudit->total_pendapatan_kamar) }}</div></div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100 flex justify-between"><h2 class="text-sm font-semibold">Pratinjau Posting Malam Ini ({{ $preview->count() }} kamar)</h2><span class="text-xs text-stone-400">Okupansi {{ $jual > 0 ? round($preview->count() / $jual * 100, 1) : 0 }}%</span></div>
        <table class="w-full text-sm"><thead class="bg-stone-50"><tr><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Kamar</th><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Tamu</th><th class="text-right px-5 py-2.5 text-xs font-semibold text-stone-400">Tarif Malam Ini</th><th class="text-center px-5 py-2.5 text-xs font-semibold text-stone-400">Status</th></tr></thead>
        <tbody class="divide-y divide-stone-50">
            @forelse($preview as $p)
            <tr><td class="px-5 py-2.5"><span class="font-bold">{{ $p['reservasi']->kamar?->nomor ?? '-' }}</span> <span class="text-xs text-stone-400">{{ $p['reservasi']->tipeKamar->nama }}</span></td>
            <td class="px-5 py-2.5">{{ $p['reservasi']->tamu->nama }}</td>
            <td class="px-5 py-2.5 text-right font-medium">{{ Rp::fmt($p['harga']) }}</td>
            <td class="px-5 py-2.5 text-center">@if($p['sudah'])<span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Sudah diposting</span>@else<span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Akan diposting</span>@endif</td></tr>
            @empty<tr><td colspan="4" class="px-5 py-10 text-center text-sm text-stone-400">Tidak ada tamu in-house malam ini</td></tr>@endforelse
        </tbody></table>
    </div>

    @if($belumDatang->count() > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
        <div class="text-sm font-semibold text-amber-900 mb-2">Tamu belum datang ({{ $belumDatang->count() }})</div>
        <div class="text-xs text-amber-800 mb-3">Putuskan sebelum menutup hari: tunggu, atau tandai no-show agar stok kamar dilepas.</div>
        <div class="space-y-1.5">
            @foreach($belumDatang as $r)
            <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2 text-sm"><span>{{ $r->tamu->nama }} · {{ $r->tipeKamar->nama }} · <span class="font-mono text-xs">{{ $r->kode_booking }}</span></span>
            @if($bisaReservasi)<a href="{{ route('reservasi.show',$r) }}" class="text-xs text-amber-700 hover:underline">Tindak lanjuti →</a>@endif</div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<div>
    <div class="bg-white rounded-xl border border-stone-200 p-5 sticky top-20">
        <h2 class="text-sm font-semibold mb-3">Jalankan Night Audit</h2>
        <div class="text-sm space-y-1.5 mb-4">
            <div class="flex justify-between"><span class="text-stone-500">Folio akan diposting</span><span class="font-semibold">{{ $preview->where('sudah', false)->count() }}</span></div>
            <div class="flex justify-between"><span class="text-stone-500">Perkiraan pendapatan kamar</span><span class="font-semibold">{{ Rp::fmt($akanDiposting) }}</span></div>
        </div>
        @if($sudahAudit)
            <div class="text-xs text-stone-500 bg-stone-50 rounded-lg px-3 py-2">Night audit hari ini sudah selesai dan tidak dapat dijalankan dua kali.</div>
        @else
        <form action="{{ route('nightaudit.run') }}" method="POST" class="space-y-2" onsubmit="return confirm('Posting biaya kamar malam ini ke semua folio in-house?')">@csrf
            <input name="catatan" placeholder="Catatan (opsional)" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
            <button class="w-full bg-stone-800 hover:bg-stone-900 text-white font-bold py-3 rounded-xl transition">🌙 Jalankan Night Audit</button>
        </form>
        <p class="text-xs text-stone-400 mt-3">Biaya kamar malam ini diposting ke folio setiap tamu in-house. Aksi ini hanya bisa dilakukan sekali per hari.</p>
        @endif
    </div>
</div>
</div>

<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Riwayat Night Audit</h2></div>
    <table class="w-full text-sm"><thead class="bg-stone-50"><tr><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Tanggal</th><th class="text-center px-5 py-2.5 text-xs font-semibold text-stone-400">In-house</th><th class="text-center px-5 py-2.5 text-xs font-semibold text-stone-400">Diposting</th><th class="text-center px-5 py-2.5 text-xs font-semibold text-stone-400">Okupansi</th><th class="text-right px-5 py-2.5 text-xs font-semibold text-stone-400">Pendapatan Kamar</th><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Oleh</th></tr></thead>
    <tbody class="divide-y divide-stone-50">
        @forelse($riwayat as $a)
        <tr class="hover:bg-stone-50"><td class="px-5 py-2.5 font-medium">{{ $a->tanggal->translatedFormat('D, d M Y') }}</td><td class="px-5 py-2.5 text-center">{{ $a->jumlah_in_house }}</td><td class="px-5 py-2.5 text-center">{{ $a->jumlah_diposting }}</td>
        <td class="px-5 py-2.5 text-center"><span class="font-semibold">{{ $a->okupansi }}%</span></td><td class="px-5 py-2.5 text-right font-medium">{{ Rp::fmt($a->total_pendapatan_kamar) }}</td><td class="px-5 py-2.5 text-xs text-stone-500">{{ $a->dijalankanOleh?->name }}{{ $a->catatan ? ' · ' . $a->catatan : '' }}</td></tr>
        @empty<tr><td colspan="6" class="px-5 py-10 text-center text-sm text-stone-400">Belum ada riwayat</td></tr>@endforelse
    </tbody></table>
    @if($riwayat->hasPages())<div class="px-5 py-3 border-t">{{ $riwayat->links() }}</div>@endif
</div>
@endsection
