@use('App\Support\Rp')
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Invoice {{ $folio->nomor_folio }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>@media print{.no-print{display:none!important}body{background:#fff!important}}</style>
</head>
<body class="bg-stone-100 p-6">
@php $r = $folio->reservasi; @endphp
<div class="no-print max-w-3xl mx-auto mb-4 flex justify-between">
    <a href="{{ route('folio.show',$folio) }}" class="text-sm text-stone-500 hover:text-stone-800">← Kembali ke folio</a>
    <button onclick="window.print()" class="bg-amber-500 hover:bg-amber-600 text-stone-900 text-sm font-semibold px-5 py-2 rounded-lg">🖨️ Cetak / Simpan PDF</button>
</div>
<div class="max-w-3xl mx-auto bg-white shadow-sm p-10 text-sm">
    <div class="flex justify-between items-start border-b-2 border-stone-800 pb-5 mb-6">
        <div><div class="text-2xl font-bold text-stone-900">{{ config('hotel.nama') }}</div><div class="text-xs text-stone-500">{{ config('hotel.alamat') }}<br>Telp {{ config('hotel.telepon') }}</div></div>
        <div class="text-right"><div class="text-lg font-bold tracking-widest text-stone-800">INVOICE</div><div class="font-mono text-xs text-stone-500">{{ $folio->nomor_folio }}</div><div class="text-xs text-stone-400">{{ ($folio->ditutup_at ?? now())->translatedFormat('d F Y') }}</div></div>
    </div>
    <div class="grid grid-cols-2 gap-6 mb-6 text-xs">
        <div><div class="text-stone-400 mb-1">TAMU</div><div class="font-semibold text-stone-900 text-sm">{{ $r->tamu->nama }}</div><div class="text-stone-500">{{ $r->tamu->alamat }}{{ $r->tamu->kota ? ', ' . $r->tamu->kota : '' }}</div><div class="text-stone-500">{{ $r->tamu->telepon }}</div></div>
        <div><div class="text-stone-400 mb-1">MENGINAP</div><div class="font-semibold text-stone-900 text-sm">{{ $r->tipeKamar->nama }}{{ $r->kamar ? ' · Kamar ' . $r->kamar->nomor : '' }}</div><div class="text-stone-500">{{ $r->tanggal_masuk->translatedFormat('d M Y') }} – {{ $r->tanggal_keluar->translatedFormat('d M Y') }} ({{ $r->jumlah_malam }} malam)</div><div class="text-stone-500">Booking {{ $r->kode_booking }}</div></div>
    </div>
    <table class="w-full mb-6"><thead><tr class="border-b border-stone-300 text-xs text-stone-500"><th class="text-left py-2 w-24">Tanggal</th><th class="text-left py-2">Deskripsi</th><th class="text-center py-2 w-12">Qty</th><th class="text-right py-2 w-28">Harga</th><th class="text-right py-2 w-32">Jumlah</th></tr></thead>
    <tbody>@foreach($folio->items as $i)<tr class="border-b border-stone-100"><td class="py-1.5 text-xs text-stone-500">{{ $i->tanggal->translatedFormat('d M Y') }}</td><td class="py-1.5">{{ $i->deskripsi }}</td><td class="py-1.5 text-center">{{ $i->qty }}</td><td class="py-1.5 text-right">{{ Rp::fmt($i->harga_satuan) }}</td><td class="py-1.5 text-right font-medium">{{ Rp::fmt($i->jumlah) }}</td></tr>@endforeach</tbody></table>
    <div class="ml-auto w-72 space-y-1.5 mb-6">
        <div class="flex justify-between"><span class="text-stone-500">Subtotal</span><span>{{ Rp::fmt($folio->subtotal) }}</span></div>
        @if((float)$folio->diskon>0)<div class="flex justify-between"><span class="text-stone-500">Diskon</span><span>− {{ Rp::fmt($folio->diskon) }}</span></div>@endif
        <div class="flex justify-between"><span class="text-stone-500">Service charge {{ config('hotel.service_persen') }}%</span><span>{{ Rp::fmt($folio->service) }}</span></div>
        <div class="flex justify-between"><span class="text-stone-500">Pajak {{ config('hotel.pajak_persen') }}%</span><span>{{ Rp::fmt($folio->pajak) }}</span></div>
        <div class="flex justify-between border-t-2 border-stone-800 pt-2 text-base"><span class="font-bold">TOTAL</span><span class="font-bold">{{ Rp::fmt($folio->total) }}</span></div>
    </div>
    @if($folio->pembayaran->count()>0)
    <div class="mb-6"><div class="text-xs text-stone-400 mb-1">PEMBAYARAN</div>
        @foreach($folio->pembayaran as $p)<div class="flex justify-between text-xs py-1 border-b border-stone-100"><span>{{ $p->created_at->translatedFormat('d M Y') }} · {{ $p->jenis_badge['label'] }} · {{ $p->metode_label }}</span><span>{{ $p->jenis==='refund'?'− ':'' }}{{ Rp::fmt($p->jumlah) }}</span></div>@endforeach
        <div class="flex justify-between font-semibold mt-2"><span>{{ $folio->sisa > 0 ? 'Sisa yang harus dibayar' : 'Status' }}</span><span class="{{ $folio->sisa>0?'text-red-600':'text-emerald-600' }}">{{ $folio->sisa > 0 ? Rp::fmt($folio->sisa) : 'LUNAS' }}</span></div>
    </div>
    @endif
    <div class="grid grid-cols-2 gap-10 mt-14 text-center text-xs text-stone-500"><div class="border-t border-stone-300 pt-2">Tanda tangan tamu</div><div class="border-t border-stone-300 pt-2">Front Office</div></div>
    <div class="text-center text-xs text-stone-400 mt-8">Terima kasih telah menginap di {{ config('hotel.nama') }}.</div>
</div>
</body>
</html>
