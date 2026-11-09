@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Reservasi ' . $reservasi->kode_booking)
@section('subtitle', $reservasi->tamu->nama . ' · ' . $reservasi->sumber_label)
@section('header-action')
<div class="flex gap-2">
    @if($reservasi->bisaCheckIn())<a href="{{ route('frontdesk.checkin',$reservasi) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">🛬 Check-in</a>@endif
    @if($reservasi->bisaCheckOut())<a href="{{ route('frontdesk.checkout',$reservasi) }}" class="bg-stone-800 hover:bg-stone-900 text-white text-sm font-semibold px-4 py-2 rounded-lg">🛫 Check-out</a>@endif
    @if($reservasi->folio)<a href="{{ route('folio.show',$reservasi->folio) }}" class="bg-amber-500 hover:bg-amber-600 text-stone-900 text-sm font-semibold px-4 py-2 rounded-lg">🧾 Folio</a>@endif
    @if($reservasi->status==='dikonfirmasi')<a href="{{ route('reservasi.edit',$reservasi) }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm px-4 py-2 rounded-lg">Ubah</a>@endif
</div>
@endsection
@section('content')
@php $folio = $reservasi->folio; @endphp
<div class="grid grid-cols-3 gap-5">
<div class="col-span-2 space-y-4">
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <div class="flex items-start justify-between mb-4">
            <div><div class="font-mono text-lg font-bold text-amber-700">{{ $reservasi->kode_booking }}</div><div class="text-xs text-stone-400">Dibuat {{ $reservasi->created_at->translatedFormat('d M Y H:i') }}@if($reservasi->dibuatOleh) oleh {{ $reservasi->dibuatOleh->name }}@endif</div></div>
            <span class="text-sm px-3 py-1 rounded-full {{ $reservasi->status_badge['class'] }}">{{ $reservasi->status_badge['label'] }}</span>
        </div>
        <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><div class="text-xs text-stone-400">Tamu</div><a href="{{ route('tamu.show',$reservasi->tamu) }}" class="font-semibold text-stone-900 hover:text-amber-700">{{ $reservasi->tamu->nama }}</a> <span class="text-xs">{{ $reservasi->tamu->tipe_badge['icon'] }}</span><div class="text-xs text-stone-400">{{ $reservasi->tamu->telepon }}</div></div>
            <div><div class="text-xs text-stone-400">Kamar</div><div class="font-semibold text-stone-900">{{ $reservasi->tipeKamar->nama }}</div><div class="text-xs text-stone-500">{{ $reservasi->kamar ? 'Kamar ' . $reservasi->kamar->nomor . ' · lantai ' . $reservasi->kamar->lantai : 'Kamar belum ditetapkan (dipilih saat check-in)' }}</div></div>
            <div><div class="text-xs text-stone-400">Check-in</div><div class="font-semibold">{{ $reservasi->tanggal_masuk->translatedFormat('l, d F Y') }}</div><div class="text-xs text-stone-400">mulai {{ config('hotel.jam_checkin') }}@if($reservasi->check_in_at) · aktual {{ $reservasi->check_in_at->format('H:i') }}@endif</div></div>
            <div><div class="text-xs text-stone-400">Check-out</div><div class="font-semibold">{{ $reservasi->tanggal_keluar->translatedFormat('l, d F Y') }}</div><div class="text-xs text-stone-400">sebelum {{ config('hotel.jam_checkout') }}@if($reservasi->check_out_at) · aktual {{ $reservasi->check_out_at->format('H:i') }}@endif</div></div>
            <div><div class="text-xs text-stone-400">Lama & tamu</div><div class="font-semibold">{{ $reservasi->jumlah_malam }} malam · {{ $reservasi->jumlah_dewasa }} dewasa @if($reservasi->jumlah_anak>0), {{ $reservasi->jumlah_anak }} anak @endif</div></div>
            <div><div class="text-xs text-stone-400">Deposit dibayar</div><div class="font-semibold">{{ Rp::fmt($reservasi->deposit) }}</div></div>
        </div>
        @if($reservasi->permintaan_khusus)<div class="mt-4 bg-amber-50 border border-amber-100 rounded-lg px-4 py-3 text-sm text-amber-900"><span class="font-semibold">Permintaan khusus:</span> {{ $reservasi->permintaan_khusus }}</div>@endif
        @if($reservasi->status==='dibatalkan')<div class="mt-4 bg-red-50 border border-red-100 rounded-lg px-4 py-3 text-sm text-red-700">Dibatalkan {{ $reservasi->dibatalkan_at?->translatedFormat('d M Y') }}: {{ $reservasi->alasan_batal }}</div>@endif
    </div>

    <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Rincian Tarif Kamar per Malam</h2></div>
        <table class="w-full text-sm"><tbody class="divide-y divide-stone-50">
            @foreach($rincian as $baris)
            <tr><td class="px-5 py-2.5">{{ $baris['tanggal']->translatedFormat('D, d M Y') }}</td>
            <td class="px-5 py-2.5 text-xs text-stone-400">@if(in_array($baris['tanggal']->dayOfWeek, config('hotel.weekend_days'), true))akhir pekan @endif</td>
            <td class="px-5 py-2.5 text-right font-medium">{{ Rp::fmt($baris['harga']) }}</td></tr>
            @endforeach
            <tr class="bg-stone-50"><td class="px-5 py-3 font-semibold" colspan="2">Total kamar</td><td class="px-5 py-3 text-right font-bold">{{ Rp::fmt(array_sum(array_column($rincian,'harga'))) }}</td></tr>
        </tbody></table>
    </div>
</div>

<div class="space-y-4">
    @if($folio)
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">Folio {{ $folio->nomor_folio }}</h2>
        <div class="space-y-1.5 text-sm">
            <div class="flex justify-between"><span class="text-stone-500">Total tagihan</span><span class="font-semibold">{{ Rp::fmt($folio->total) }}</span></div>
            <div class="flex justify-between"><span class="text-stone-500">Terbayar</span><span class="font-semibold text-emerald-600">{{ Rp::fmt($folio->terbayar) }}</span></div>
            <div class="flex justify-between border-t border-stone-100 pt-1.5"><span class="text-stone-500">Sisa</span><span class="font-bold {{ $folio->sisa>0?'text-red-500':'text-stone-900' }}">{{ Rp::fmt($folio->sisa) }}</span></div>
        </div>
        <a href="{{ route('folio.show',$folio) }}" class="block text-center text-xs text-amber-700 hover:underline mt-3">Buka folio lengkap →</a>
    </div>
    @endif

    @if($reservasi->status==='check_in' && $kamarPilihan->count() > 0)
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">🔁 Pindah Kamar</h2>
        <form action="{{ route('frontdesk.ganti-kamar',$reservasi) }}" method="POST" class="space-y-2">@csrf
            <select name="kamar_id" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">@foreach($kamarPilihan as $k)<option value="{{ $k->id }}">Kamar {{ $k->nomor }} (lantai {{ $k->lantai }})</option>@endforeach</select>
            <input name="alasan" placeholder="Alasan (opsional)" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
            <button class="w-full bg-stone-800 text-white text-sm py-2 rounded-lg">Pindahkan</button>
        </form>
    </div>
    @endif

    @if(in_array($reservasi->status,['dikonfirmasi','check_in'],true))
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">➕ Perpanjang Menginap</h2>
        <form action="{{ route('frontdesk.perpanjang',$reservasi) }}" method="POST" class="space-y-2">@csrf
            <label class="block text-xs text-stone-500">Check-out baru</label>
            <input type="date" name="tanggal_keluar" min="{{ $reservasi->tanggal_keluar->copy()->addDay()->toDateString() }}" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
            <button class="w-full bg-amber-500 hover:bg-amber-600 text-stone-900 text-sm font-semibold py-2 rounded-lg">Perpanjang</button>
        </form>
    </div>
    @endif

    @if($reservasi->status==='dikonfirmasi')
    <div class="bg-white rounded-xl border border-red-100 p-5">
        <h2 class="text-sm font-semibold mb-3 text-red-700">Batalkan Reservasi</h2>
        <form action="{{ route('reservasi.batal',$reservasi) }}" method="POST" class="space-y-2" onsubmit="return confirm('Batalkan reservasi ini?')">@csrf @method('PATCH')
            <input name="alasan_batal" placeholder="Alasan pembatalan" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
            @if($folio && (float)$folio->terbayar > 0)<label class="flex items-center gap-2 text-xs text-stone-600"><input type="checkbox" name="refund_deposit" value="1" class="rounded"/> Kembalikan deposit {{ Rp::fmt($folio->terbayar) }}</label>@endif
            <button class="w-full bg-red-600 hover:bg-red-700 text-white text-sm py-2 rounded-lg">Batalkan</button>
        </form>
        @if(today()->gte($reservasi->tanggal_masuk))
        <form action="{{ route('reservasi.no-show',$reservasi) }}" method="POST" class="mt-3" onsubmit="return confirm('Tandai tamu tidak datang (no-show)?')">@csrf @method('PATCH')
            <button class="w-full bg-orange-100 hover:bg-orange-200 text-orange-800 text-sm py-2 rounded-lg">Tandai No-Show</button>
        </form>
        @endif
    </div>
    @endif
</div>
</div>
@endsection
