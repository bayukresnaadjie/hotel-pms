@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Ketersediaan Kamar')
@section('subtitle', $mulai->translatedFormat('d M') . ' – ' . $akhir->copy()->subDay()->translatedFormat('d M Y') . ' · ' . $jumlahHari . ' malam')
@section('header-action')
<div class="flex items-center gap-2">
    <a href="{{ route('ketersediaan.index',['mulai'=>$mulai->copy()->subDays(7)->toDateString()]) }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm px-3 py-2 rounded-lg">← 7 hari</a>
    <a href="{{ route('ketersediaan.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm px-3 py-2 rounded-lg">Hari ini</a>
    <a href="{{ route('ketersediaan.index',['mulai'=>$mulai->copy()->addDays(7)->toDateString()]) }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm px-3 py-2 rounded-lg">7 hari →</a>
    <form method="GET" class="flex gap-1"><input type="date" name="mulai" value="{{ $mulai->toDateString() }}" class="border border-stone-200 rounded-lg px-2 py-1.5 text-sm"/><button class="bg-stone-800 text-white text-sm px-3 rounded-lg">Pergi</button></form>
</div>
@endsection
@section('content')

<div class="bg-white rounded-xl border border-stone-200 overflow-x-auto mb-5">
    <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Sisa Kamar per Tipe</h2><p class="text-xs text-stone-400">Angka besar = kamar tersedia pada malam itu. Merah = penuh.</p></div>
    <table class="w-full text-sm">
        <thead><tr class="bg-stone-50">
            <th class="text-left px-4 py-2 text-xs font-semibold text-stone-400 min-w-40">Tipe</th>
            @foreach($tanggal as $d)
            <th class="px-1 py-2 text-center text-xs font-semibold {{ in_array($d->dayOfWeek, config('hotel.weekend_days'), true) ? 'text-amber-600 bg-amber-50' : 'text-stone-400' }}"><div>{{ $d->translatedFormat('D') }}</div><div class="font-bold">{{ $d->format('d') }}</div></th>
            @endforeach
        </tr></thead>
        <tbody class="divide-y divide-stone-100">
            @foreach($matriks as $m)
            <tr>
                <td class="px-4 py-2"><div class="font-semibold text-stone-900">{{ $m['tipe']->nama }}</div><div class="text-xs text-stone-400">{{ $m['total'] }} kamar dijual</div></td>
                @foreach($m['hari'] as $h)
                @php
                    $rasio = $m['total'] > 0 ? $h['tersedia'] / $m['total'] : 0;
                    $warna = $h['tersedia'] === 0 ? 'bg-red-100 text-red-700' : ($rasio <= 0.25 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700');
                @endphp
                <td class="px-1 py-1.5 text-center"><div class="rounded-lg {{ $warna }} py-1" title="{{ Rp::fmt($h['harga']) }} / malam"><div class="font-bold">{{ $h['tersedia'] }}</div><div style="font-size:9px" class="opacity-70">{{ round($h['harga'] / 1000) }}k</div></div></td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="bg-white rounded-xl border border-stone-200 overflow-x-auto">
    <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Tape Chart Kamar</h2><p class="text-xs text-stone-400">Menampilkan tamu yang sudah ditempatkan di kamar tertentu. Reservasi yang belum check-in belum memiliki kamar, lihat tabel di atas.</p></div>
    <table class="w-full text-sm" style="table-layout:fixed">
        <thead><tr class="bg-stone-50">
            <th class="text-left px-3 py-2 text-xs font-semibold text-stone-400" style="width:90px">Kamar</th>
            @foreach($tanggal as $d)<th class="px-0 py-2 text-center text-xs font-semibold {{ in_array($d->dayOfWeek, config('hotel.weekend_days'), true) ? 'text-amber-600' : 'text-stone-400' }}">{{ $d->format('d') }}</th>@endforeach
        </tr></thead>
        <tbody>
            @foreach($kamar as $k)
            @php
                $daftar = ($reservasiKamar[$k->id] ?? collect())->sortBy('tanggal_masuk')->values();
                $i = 0;
            @endphp
            <tr class="border-t border-stone-100">
                <td class="px-3 py-1.5 text-xs font-bold text-stone-800">{{ $k->nomor }} <span class="font-normal text-stone-400">{{ $k->tipeKamar->kode }}</span></td>
                @while($i < $jumlahHari)
                    @php
                        $d = $tanggal[$i];
                        $r = $daftar->first(fn($x) => $x->tanggal_masuk->lte($d) && $x->tanggal_keluar->gt($d));
                    @endphp
                    @if($r)
                        @php $span = 0; while ($i + $span < $jumlahHari && $r->tanggal_keluar->gt($tanggal[$i + $span])) { $span++; } @endphp
                        <td colspan="{{ $span }}" class="p-0.5"><a href="{{ route('reservasi.show',$r) }}" class="block truncate rounded px-2 py-1 text-xs font-medium {{ $r->status === 'check_in' ? 'bg-emerald-500 text-white' : 'bg-sky-500 text-white' }}" title="{{ $r->tamu->nama }} · {{ $r->tanggal_masuk->translatedFormat('d M') }} → {{ $r->tanggal_keluar->translatedFormat('d M') }}">{{ $r->tamu->nama }}</a></td>
                        @php $i += $span; @endphp
                    @else
                        <td class="border-l border-stone-50 {{ $k->status_kamar === 'rusak' ? 'bg-stone-200' : '' }}">&nbsp;</td>
                        @php $i++; @endphp
                    @endif
                @endwhile
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-5 py-3 border-t border-stone-100 flex gap-4 text-xs text-stone-500"><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span>In-house</span><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-500"></span>Sudah ditempatkan</span><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-stone-300"></span>Kamar rusak</span></div>
</div>
@endsection
