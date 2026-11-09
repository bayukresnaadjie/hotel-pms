@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Check-out')
@section('subtitle', $reservasi->kode_booking . ' · ' . $reservasi->tamu->nama . ' · Kamar ' . ($reservasi->kamar?->nomor ?? '-'))
@section('content')
@php
$k = $proyeksi['komponen'];
$subtotalProyeksi = (float) $folio->subtotal + $proyeksi['tambahan'];
$sisa = $proyeksi['sisa'];
$berubah = !$keluarAktual->isSameDay($reservasi->tanggal_keluar);
@endphp
<div class="grid grid-cols-3 gap-5">
<div class="col-span-2 space-y-4">
    @if($berubah)
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-900">
        @if($keluarAktual->gt($reservasi->tanggal_keluar))⚠️ <b>Melewati jadwal.</b> Jadwal check-out {{ $reservasi->tanggal_keluar->translatedFormat('d M') }}, malam tambahan sampai {{ $keluarAktual->translatedFormat('d M') }} ikut ditagihkan.
        @else ℹ️ <b>Check-out lebih awal.</b> Jadwal awal {{ $reservasi->tanggal_keluar->translatedFormat('d M') }}; hanya malam yang benar-benar dihuni yang ditagihkan dan stok kamar dilepas.@endif
    </div>
    @endif

    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100 flex justify-between"><h2 class="text-sm font-semibold">Rincian Tagihan</h2><a href="{{ route('folio.show',$folio) }}" class="text-xs text-amber-700 hover:underline">Buka folio</a></div>
        <table class="w-full text-sm"><tbody class="divide-y divide-stone-50">
            @foreach($folio->items as $i)
            <tr><td class="px-5 py-2 text-xs text-stone-400 w-28">{{ $i->tanggal->translatedFormat('d M') }}</td><td class="px-5 py-2">{{ $i->tipe_icon }} {{ $i->deskripsi }}@if($i->qty>1) <span class="text-xs text-stone-400">× {{ $i->qty }}</span>@endif</td><td class="px-5 py-2 text-right {{ (float)$i->jumlah<0?'text-emerald-600':'' }}">{{ Rp::fmt($i->jumlah) }}</td></tr>
            @endforeach
            @foreach($proyeksi['belum'] as $b)
            <tr class="bg-amber-50/60"><td class="px-5 py-2 text-xs text-amber-700 w-28">{{ $b['tanggal']->translatedFormat('d M') }}</td><td class="px-5 py-2 text-amber-900">🛏️ Malam kamar <span class="text-xs">(akan diposting saat check-out)</span></td><td class="px-5 py-2 text-right text-amber-900">{{ Rp::fmt($b['harga']) }}</td></tr>
            @endforeach
        </tbody></table>
    </div>

    @if($folio->pembayaran->count() > 0)
    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Pembayaran Sebelumnya</h2></div>
        <table class="w-full text-sm"><tbody class="divide-y divide-stone-50">
            @foreach($folio->pembayaran as $p)
            <tr><td class="px-5 py-2 text-xs text-stone-400 w-28">{{ $p->created_at->translatedFormat('d M H:i') }}</td><td class="px-5 py-2"><span class="text-xs px-2 py-0.5 rounded-full {{ $p->jenis_badge['class'] }}">{{ $p->jenis_badge['label'] }}</span> <span class="text-xs text-stone-500">{{ $p->metode_label }}</span></td><td class="px-5 py-2 text-right font-medium">{{ Rp::fmt($p->jumlah) }}</td></tr>
            @endforeach
        </tbody></table>
    </div>
    @endif
</div>

<div>
<form action="{{ route('frontdesk.checkout.store',$reservasi) }}" method="POST" class="bg-white rounded-xl border border-stone-200 p-5 sticky top-20">@csrf
    <h2 class="text-sm font-semibold mb-3">Total Akhir</h2>
    <div class="space-y-1.5 text-sm">
        <div class="flex justify-between"><span class="text-stone-500">Subtotal</span><span>{{ Rp::fmt($subtotalProyeksi) }}</span></div>
        @if((float)$folio->diskon>0)<div class="flex justify-between"><span class="text-stone-500">Diskon</span><span class="text-emerald-600">− {{ Rp::fmt($folio->diskon) }}</span></div>@endif
        <div class="flex justify-between"><span class="text-stone-500">Service {{ config('hotel.service_persen') }}%</span><span>{{ Rp::fmt($k['service']) }}</span></div>
        <div class="flex justify-between"><span class="text-stone-500">Pajak {{ config('hotel.pajak_persen') }}%</span><span>{{ Rp::fmt($k['pajak']) }}</span></div>
        <div class="flex justify-between border-t border-stone-100 pt-2 text-base"><span class="font-semibold">Total</span><span class="font-bold">{{ Rp::fmt($proyeksi['total']) }}</span></div>
        <div class="flex justify-between"><span class="text-stone-500">Sudah dibayar</span><span class="text-emerald-600">{{ Rp::fmt($folio->terbayar) }}</span></div>
        <div class="flex justify-between border-t border-stone-100 pt-2"><span class="font-semibold">{{ $sisa >= 0 ? 'Sisa dibayar' : 'Kembalian' }}</span><span class="font-bold text-lg {{ $sisa>0?'text-red-500':'text-emerald-600' }}">{{ Rp::fmt(abs($sisa)) }}</span></div>
    </div>

    @if(abs($sisa) > 0.5)
    <div class="mt-4 space-y-2">
        <label class="block text-xs font-medium text-stone-600">{{ $sisa>0 ? 'Metode pembayaran *' : 'Metode pengembalian' }}</label>
        <select name="metode" {{ $sisa>0 ? 'required' : '' }} class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">
            @if($sisa>0)<option value="">— pilih —</option>@endif
            @foreach(['tunai'=>'Tunai','kartu_debit'=>'Kartu Debit','kartu_kredit'=>'Kartu Kredit','transfer'=>'Transfer Bank','qris'=>'QRIS'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
        </select>
        <input name="referensi" placeholder="No. referensi / approval (opsional)" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
    </div>
    @else
    <div class="mt-4 text-xs text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2">Tagihan sudah lunas.</div>
    @endif

    <button class="w-full mt-4 bg-stone-800 hover:bg-stone-900 text-white font-bold py-3 rounded-xl transition" onclick="return confirm('Selesaikan check-out dan tutup folio?')">🛫 Selesaikan Check-out</button>
    <a href="{{ route('frontdesk.index') }}" class="block text-center text-xs text-stone-400 hover:text-stone-600 mt-3">Batal</a>
</form>
</div>
</div>
@endsection
