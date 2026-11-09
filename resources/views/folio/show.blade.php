@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Folio ' . $folio->nomor_folio)
@section('subtitle', $folio->reservasi->tamu->nama . ' · ' . $folio->reservasi->kode_booking)
@section('header-action')
<div class="flex gap-2 items-center">
    <span class="text-xs px-3 py-1 rounded-full {{ $folio->status_badge['class'] }}">{{ $folio->status_badge['label'] }}</span>
    <a href="{{ route('folio.invoice',$folio) }}" target="_blank" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm px-4 py-2 rounded-lg">🖨️ Invoice</a>
    @if($folio->reservasi->bisaCheckOut() && auth()->user()->punyaRole('admin','frontdesk'))<a href="{{ route('frontdesk.checkout',$folio->reservasi) }}" class="bg-stone-800 hover:bg-stone-900 text-white text-sm font-semibold px-4 py-2 rounded-lg">🛫 Check-out</a>@endif
    <a href="{{ route('reservasi.show',$folio->reservasi) }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm px-4 py-2 rounded-lg">Reservasi</a>
</div>
@endsection
@section('content')
@php
$r = $folio->reservasi;
$terbuka = $folio->status === 'terbuka';
$inHouse = $r->status === 'check_in';
$u = auth()->user();
@endphp
<div class="grid grid-cols-3 gap-5">
<div class="col-span-2 space-y-4">

    <div class="bg-white rounded-xl border border-stone-200 p-5 grid grid-cols-3 gap-4 text-sm">
        <div><div class="text-xs text-stone-400">Tamu</div><div class="font-semibold">{{ $r->tamu->nama }}</div></div>
        <div><div class="text-xs text-stone-400">Kamar</div><div class="font-semibold">{{ $r->kamar ? $r->kamar->nomor . ' · ' . $r->tipeKamar->nama : $r->tipeKamar->nama }}</div></div>
        <div><div class="text-xs text-stone-400">Menginap</div><div class="font-semibold">{{ $r->tanggal_masuk->translatedFormat('d M') }} → {{ $r->tanggal_keluar->translatedFormat('d M Y') }}</div></div>
    </div>

    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Rincian Biaya</h2></div>
        @forelse($itemPerTanggal as $tanggal => $daftar)
        <div class="px-5 py-1.5 bg-stone-50 text-xs font-semibold text-stone-500">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}</div>
        <table class="w-full text-sm"><tbody class="divide-y divide-stone-50">
            @foreach($daftar as $i)
            @php $boleh = $terbuka && ($i->tipe==='layanan' || $i->tipe==='lainnya' || ($i->tipe==='kamar' && $u->punyaRole('admin')) || ($i->tipe==='diskon' && $u->punyaRole('admin','finance'))); @endphp
            <tr class="hover:bg-stone-50"><td class="px-5 py-2.5">{{ $i->tipe_icon }} {{ $i->deskripsi }}@if($i->qty>1)<span class="text-xs text-stone-400"> × {{ $i->qty }} @ {{ Rp::fmt($i->harga_satuan) }}</span>@endif</td>
            <td class="px-5 py-2.5 text-right font-medium {{ (float)$i->jumlah<0?'text-emerald-600':'' }}">{{ Rp::fmt($i->jumlah) }}</td>
            <td class="px-3 py-2.5 w-10 text-right">@if($boleh)<form action="{{ route('folio.item.destroy',[$folio,$i]) }}" method="POST" onsubmit="return confirm('Hapus item ini?')">@csrf @method('DELETE')<button class="text-stone-300 hover:text-red-500" title="Hapus">✕</button></form>@endif</td></tr>
            @endforeach
        </tbody></table>
        @empty<div class="px-5 py-10 text-center text-sm text-stone-400">Belum ada biaya. Biaya kamar diposting otomatis oleh night audit atau saat check-out.</div>@endforelse
    </div>

    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Riwayat Pembayaran</h2></div>
        <table class="w-full text-sm"><tbody class="divide-y divide-stone-50">
            @forelse($folio->pembayaran as $p)
            <tr><td class="px-5 py-2.5"><span class="font-mono text-xs text-stone-500">{{ $p->nomor_pembayaran }}</span><div class="text-xs text-stone-400">{{ $p->created_at->translatedFormat('d M Y H:i') }} · {{ $p->diterimaOleh?->name }}</div></td>
            <td class="px-5 py-2.5"><span class="text-xs px-2 py-0.5 rounded-full {{ $p->jenis_badge['class'] }}">{{ $p->jenis_badge['label'] }}</span> <span class="text-xs text-stone-500">{{ $p->metode_label }}</span>@if($p->referensi)<div class="text-xs text-stone-400">{{ $p->referensi }}</div>@endif</td>
            <td class="px-5 py-2.5 text-right font-semibold {{ $p->jenis==='refund'?'text-red-500':'text-emerald-600' }}">{{ $p->jenis==='refund'?'− ':'' }}{{ Rp::fmt($p->jumlah) }}</td></tr>
            @empty<tr><td class="px-5 py-8 text-center text-sm text-stone-400">Belum ada pembayaran</td></tr>@endforelse
        </tbody></table>
    </div>
</div>

<div class="space-y-4">
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">Ringkasan Tagihan</h2>
        <div class="space-y-1.5 text-sm">
            <div class="flex justify-between"><span class="text-stone-500">Subtotal</span><span>{{ Rp::fmt($folio->subtotal) }}</span></div>
            @if((float)$folio->diskon>0)<div class="flex justify-between"><span class="text-stone-500">Diskon</span><span class="text-emerald-600">− {{ Rp::fmt($folio->diskon) }}</span></div>@endif
            <div class="flex justify-between"><span class="text-stone-500">Service {{ config('hotel.service_persen') }}%</span><span>{{ Rp::fmt($folio->service) }}</span></div>
            <div class="flex justify-between"><span class="text-stone-500">Pajak {{ config('hotel.pajak_persen') }}%</span><span>{{ Rp::fmt($folio->pajak) }}</span></div>
            <div class="flex justify-between border-t border-stone-100 pt-2"><span class="font-semibold">Total</span><span class="font-bold text-base">{{ Rp::fmt($folio->total) }}</span></div>
            <div class="flex justify-between"><span class="text-stone-500">Terbayar</span><span class="text-emerald-600 font-medium">{{ Rp::fmt($folio->terbayar) }}</span></div>
            <div class="flex justify-between border-t border-stone-100 pt-2"><span class="font-semibold">Sisa</span><span class="font-bold text-lg {{ $folio->sisa>0?'text-red-500':'text-emerald-600' }}">{{ Rp::fmt($folio->sisa) }}</span></div>
        </div>
    </div>

    @if($terbuka && $inHouse)
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">➕ Tambah Biaya</h2>
        <form action="{{ route('folio.item.store',$folio) }}" method="POST" class="space-y-2">@csrf
            <select name="tipe" id="tipe-item" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">
                <option value="layanan">Layanan hotel</option><option value="lainnya">Biaya lain (manual)</option>
                @if($u->punyaRole('admin','finance'))<option value="diskon">Diskon / kompensasi</option>@endif
            </select>
            <div id="f-layanan" class="space-y-2">
                <select name="layanan_id" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">@foreach($layanan as $l)<option value="{{ $l->id }}">{{ $l->nama }} — {{ Rp::fmt($l->harga) }}</option>@endforeach</select>
                <input type="number" name="qty" value="1" min="1" max="99" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm" placeholder="Jumlah"/>
            </div>
            <div id="f-manual" class="space-y-2 hidden">
                <input name="deskripsi" placeholder="Deskripsi" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
                <input type="number" name="harga" min="1" placeholder="Nominal (Rp)" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
            </div>
            <input type="date" name="tanggal" max="{{ today()->toDateString() }}" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm" title="Tanggal biaya (kosong = hari ini)"/>
            <button class="w-full bg-stone-800 hover:bg-stone-900 text-white text-sm py-2 rounded-lg">Tambahkan</button>
        </form>
    </div>
    @endif

    @if($terbuka)
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">💳 Catat Pembayaran</h2>
        <form action="{{ route('folio.bayar',$folio) }}" method="POST" class="space-y-2">@csrf
            <select name="jenis" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"><option value="pembayaran">Pembayaran</option><option value="deposit">Deposit</option>@if($u->punyaRole('admin','finance'))<option value="refund">Refund</option>@endif</select>
            <select name="metode" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">@foreach(['tunai'=>'Tunai','kartu_debit'=>'Kartu Debit','kartu_kredit'=>'Kartu Kredit','transfer'=>'Transfer Bank','qris'=>'QRIS'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
            <input type="number" name="jumlah" min="1" value="{{ $folio->sisa>0 ? (int) $folio->sisa : '' }}" required placeholder="Jumlah (Rp)" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
            <input name="referensi" placeholder="No. referensi (opsional)" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
            <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 rounded-lg">Catat Pembayaran</button>
        </form>
    </div>
    @endif
</div>
</div>
<script>
const tipeItem = document.getElementById('tipe-item');
if (tipeItem) {
    const ubah = () => {
        const layanan = tipeItem.value === 'layanan';
        document.getElementById('f-layanan').classList.toggle('hidden', !layanan);
        document.getElementById('f-manual').classList.toggle('hidden', layanan);
        document.querySelectorAll('#f-layanan select, #f-layanan input').forEach(e => e.disabled = !layanan);
        document.querySelectorAll('#f-manual input').forEach(e => e.disabled = layanan);
    };
    tipeItem.addEventListener('change', ubah);
    ubah();
}
</script>
@endsection
