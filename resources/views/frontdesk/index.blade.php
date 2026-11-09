@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Papan Front Desk')
@section('subtitle', $hariIni->translatedFormat('l, d F Y'))
@section('header-action')<a href="{{ route('reservasi.create') }}" class="bg-amber-500 hover:bg-amber-600 text-stone-900 text-sm font-semibold px-4 py-2 rounded-lg">+ Walk-in / Reservasi</a>@endsection
@section('content')

<div class="bg-white rounded-xl border border-stone-200 px-5 py-3 mb-4 flex flex-wrap items-center gap-x-6 gap-y-2">
    <span class="text-xs font-semibold text-stone-500">Kamar siap jual sekarang:</span>
    @foreach($tipeList as $t)
    @php $n = $kamarSiap[$t->id] ?? 0; @endphp
    <span class="text-sm"><span class="text-stone-500">{{ $t->nama }}</span> <span class="font-bold {{ $n===0?'text-red-500':'text-emerald-600' }}">{{ $n }}</span></span>
    @endforeach
</div>

<div class="grid grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-xl border border-stone-200">
        <div class="px-5 py-3 border-b border-stone-100 flex justify-between items-center"><h2 class="text-sm font-semibold">🛬 Kedatangan ({{ $kedatangan->count() }})</h2></div>
        <div class="divide-y divide-stone-50">
            @forelse($kedatangan as $r)
            @php $terlambat = $r->tanggal_masuk->lt($hariIni); @endphp
            <div class="px-5 py-3.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('reservasi.show',$r) }}" class="text-sm font-semibold text-stone-900 hover:text-amber-700">{{ $r->tamu->nama }}</a> @if($r->tamu->tipe==='vip')⭐@endif
                        <div class="text-xs text-stone-500">{{ $r->tipeKamar->nama }} · {{ $r->jumlah_malam }} malam · {{ $r->jumlah_dewasa }}D{{ $r->jumlah_anak>0 ? ' '.$r->jumlah_anak.'A' : '' }} · <span class="font-mono">{{ $r->kode_booking }}</span></div>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                            @if($terlambat)<span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">Seharusnya datang {{ $r->tanggal_masuk->translatedFormat('d M') }}</span>@endif
                            @if((float)$r->deposit>0)<span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full">Deposit {{ Rp::fmt($r->deposit) }}</span>@endif
                        </div>
                        @if($r->permintaan_khusus)<div class="text-xs text-amber-800 mt-1.5">📝 {{ $r->permintaan_khusus }}</div>@endif
                    </div>
                    <a href="{{ route('frontdesk.checkin',$r) }}" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-3 py-2 rounded-lg flex-shrink-0">Check-in</a>
                </div>
            </div>
            @empty<div class="px-5 py-10 text-center text-sm text-stone-400">Tidak ada kedatangan tersisa 🎉</div>@endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl border border-stone-200">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">🛫 Keberangkatan ({{ $keberangkatan->count() }})</h2></div>
        <div class="divide-y divide-stone-50">
            @forelse($keberangkatan as $r)
            @php $overstay = $r->tanggal_keluar->lt($hariIni); $sisa = (float) ($r->folio?->sisa ?? 0); @endphp
            <div class="px-5 py-3.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('reservasi.show',$r) }}" class="text-sm font-semibold text-stone-900 hover:text-amber-700">{{ $r->tamu->nama }}</a>
                        <div class="text-xs text-stone-500">Kamar <b>{{ $r->kamar?->nomor ?? '-' }}</b> · sejak {{ $r->tanggal_masuk->translatedFormat('d M') }}</div>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                            @if($overstay)<span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Lewat jadwal ({{ $r->tanggal_keluar->translatedFormat('d M') }})</span>@endif
                            <span class="text-xs {{ $sisa>0?'bg-red-50 text-red-600':'bg-emerald-50 text-emerald-700' }} px-2 py-0.5 rounded-full">Sisa {{ Rp::fmt($sisa) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5 flex-shrink-0">
                        <a href="{{ route('frontdesk.checkout',$r) }}" class="text-xs bg-stone-800 hover:bg-stone-900 text-white font-semibold px-3 py-2 rounded-lg text-center">Check-out</a>
                        @if($r->folio)<a href="{{ route('folio.show',$r->folio) }}" class="text-xs text-amber-700 hover:underline text-center">Folio</a>@endif
                    </div>
                </div>
            </div>
            @empty<div class="px-5 py-10 text-center text-sm text-stone-400">Tidak ada keberangkatan tersisa</div>@endforelse
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">🛌 Tamu In-House Lainnya ({{ $inHouse->count() }})</h2></div>
    <table class="w-full text-sm"><thead class="bg-stone-50"><tr>
        <th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Kamar</th><th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Tamu</th>
        <th class="text-left px-5 py-2.5 text-xs font-semibold text-stone-400">Menginap</th><th class="text-right px-5 py-2.5 text-xs font-semibold text-stone-400">Tagihan Berjalan</th>
        <th class="text-right px-5 py-2.5 text-xs font-semibold text-stone-400">Sisa</th><th class="px-5 py-2.5"></th></tr></thead>
    <tbody class="divide-y divide-stone-50">
        @forelse($inHouse as $r)
        <tr class="hover:bg-stone-50"><td class="px-5 py-2.5"><span class="font-bold text-stone-900">{{ $r->kamar?->nomor ?? '-' }}</span> <span class="text-xs text-stone-400">{{ $r->tipeKamar->nama }}</span></td>
        <td class="px-5 py-2.5">{{ $r->tamu->nama }} @if($r->tamu->tipe==='vip')⭐@endif</td>
        <td class="px-5 py-2.5 text-xs text-stone-600">{{ $r->tanggal_masuk->translatedFormat('d M') }} → {{ $r->tanggal_keluar->translatedFormat('d M') }}</td>
        <td class="px-5 py-2.5 text-right text-xs">{{ Rp::fmt($r->folio?->total ?? 0) }}</td>
        <td class="px-5 py-2.5 text-right font-semibold {{ ($r->folio?->sisa ?? 0)>0?'text-red-500':'text-stone-400' }}">{{ Rp::fmt($r->folio?->sisa ?? 0) }}</td>
        <td class="px-5 py-2.5 text-right"><a href="{{ route('reservasi.show',$r) }}" class="text-xs text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1 rounded-lg">Detail</a></td></tr>
        @empty<tr><td colspan="6" class="px-5 py-8 text-center text-sm text-stone-400">Tidak ada tamu in-house lainnya</td></tr>@endforelse
    </tbody></table>
</div>
@endsection
