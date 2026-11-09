@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Dashboard')
@section('subtitle', config('hotel.alamat'))
@section('header-action')
@if(auth()->user()->punyaRole('admin','frontdesk'))
<a href="{{ route('reservasi.create') }}" class="bg-amber-500 hover:bg-amber-600 text-stone-900 text-sm font-semibold px-4 py-2 rounded-lg transition">+ Reservasi Baru</a>
@endif
@endsection
@section('content')

@if($stat['terlambat_datang'] > 0 || $stat['overstay'] > 0 || $stat['kamar_rusak'] > 0)
<div class="flex flex-wrap gap-2 mb-4">
    @if($stat['terlambat_datang'] > 0)<span class="text-xs bg-amber-100 text-amber-800 px-3 py-1.5 rounded-full">⏰ {{ $stat['terlambat_datang'] }} tamu terlambat datang</span>@endif
    @if($stat['overstay'] > 0)<span class="text-xs bg-red-100 text-red-700 px-3 py-1.5 rounded-full">⚠️ {{ $stat['overstay'] }} tamu melewati tanggal check-out</span>@endif
    @if($stat['kamar_rusak'] > 0)<span class="text-xs bg-stone-200 text-stone-700 px-3 py-1.5 rounded-full">🔧 {{ $stat['kamar_rusak'] }} kamar rusak (out of order)</span>@endif
</div>
@endif

<div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-5">
    <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl p-4 text-white col-span-2 xl:col-span-1">
        <div class="text-xs text-amber-100 mb-1">Okupansi Malam Ini</div>
        <div class="text-3xl font-bold">{{ $stat['okupansi'] }}%</div>
        <div class="text-xs text-amber-100 mt-1">{{ $stat['in_house'] }} dari {{ $stat['jual'] }} kamar</div>
    </div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Kedatangan Hari Ini</div><div class="text-2xl font-bold text-stone-900">{{ $stat['kedatangan'] }}</div><div class="text-xs text-stone-400">belum check-in</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Keberangkatan</div><div class="text-2xl font-bold text-stone-900">{{ $stat['keberangkatan'] }}</div><div class="text-xs text-stone-400">belum check-out</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4"><div class="text-xs text-stone-400 mb-1">Kamar Kotor</div><div class="text-2xl font-bold {{ $stat['kamar_kotor']>0?'text-red-500':'text-stone-300' }}">{{ $stat['kamar_kotor'] }}</div><div class="text-xs text-stone-400">{{ $stat['tugas_hk'] }} tugas HK aktif</div></div>
    <div class="bg-white rounded-xl border border-stone-200 p-4 col-span-2"><div class="text-xs text-stone-400 mb-1">Kas Masuk Hari Ini</div><div class="text-2xl font-bold text-emerald-600">{{ Rp::fmt($stat['kas_masuk']) }}</div><div class="text-xs text-stone-400">deposit + pembayaran − refund</div></div>
</div>

<div class="grid grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-xl border border-stone-200">
        <div class="px-5 py-3 border-b border-stone-100 flex justify-between"><h2 class="text-sm font-semibold">🛬 Kedatangan Hari Ini</h2>@if(auth()->user()->punyaRole('admin','frontdesk'))<a href="{{ route('frontdesk.index') }}" class="text-xs text-amber-600 hover:underline">Papan front desk</a>@endif</div>
        <div class="divide-y divide-stone-50">
            @forelse($kedatangan as $r)
            <div class="px-5 py-3 flex items-center justify-between gap-3">
                <div class="min-w-0"><div class="text-sm font-medium text-stone-900 truncate">{{ $r->tamu->nama }} @if($r->tamu->tipe==='vip')<span title="VIP">⭐</span>@endif</div>
                <div class="text-xs text-stone-400">{{ $r->tipeKamar->nama }} · {{ $r->jumlah_malam }} malam · {{ $r->kode_booking }}</div></div>
                @if(auth()->user()->punyaRole('admin','frontdesk'))<a href="{{ route('frontdesk.checkin',$r) }}" class="text-xs bg-emerald-600 text-white px-3 py-1.5 rounded-lg hover:bg-emerald-700 flex-shrink-0">Check-in</a>@endif
            </div>
            @empty<div class="px-5 py-8 text-center text-sm text-stone-400">Semua tamu hari ini sudah check-in 🎉</div>@endforelse
        </div>
    </div>
    <div class="bg-white rounded-xl border border-stone-200">
        <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">🛫 Keberangkatan Hari Ini</h2></div>
        <div class="divide-y divide-stone-50">
            @forelse($keberangkatan as $r)
            <div class="px-5 py-3 flex items-center justify-between gap-3">
                <div class="min-w-0"><div class="text-sm font-medium text-stone-900 truncate">{{ $r->tamu->nama }}</div>
                <div class="text-xs text-stone-400">Kamar {{ $r->kamar?->nomor ?? '-' }} · sisa tagihan {{ Rp::fmt($r->folio?->sisa ?? 0) }}</div></div>
                @if(auth()->user()->punyaRole('admin','frontdesk'))<a href="{{ route('frontdesk.checkout',$r) }}" class="text-xs bg-stone-800 text-white px-3 py-1.5 rounded-lg hover:bg-stone-900 flex-shrink-0">Check-out</a>@endif
            </div>
            @empty<div class="px-5 py-8 text-center text-sm text-stone-400">Tidak ada keberangkatan tersisa</div>@endforelse
        </div>
    </div>
</div>

<div class="grid grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-4">🛏️ Ketersediaan Malam Ini</h2>
        <div class="space-y-3">
            @foreach($ketersediaan as $k)
            @php $terisi = $k['total'] - $k['tersedia']; $persen = $k['total'] > 0 ? ($terisi / $k['total']) * 100 : 0; @endphp
            <div>
                <div class="flex justify-between text-xs mb-1"><span class="font-medium text-stone-700">{{ $k['tipe']->nama }}</span><span class="{{ $k['tersedia']===0?'text-red-500 font-bold':'text-stone-500' }}">{{ $k['tersedia'] }} / {{ $k['total'] }} tersedia</span></div>
                <div class="bg-stone-100 rounded-full h-2"><div class="h-2 rounded-full {{ $k['tersedia']===0?'bg-red-400':'bg-amber-500' }}" style="width:{{ $persen }}%"></div></div>
                <div class="text-xs text-stone-400 mt-0.5">{{ Rp::fmt($k['harga']) }} / malam</div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-4">🧹 Kondisi Kamar</h2>
        @foreach(['siap'=>['Siap jual','bg-emerald-500'],'kotor'=>['Kotor','bg-red-400'],'dibersihkan'=>['Sedang dibersihkan','bg-yellow-400'],'rusak'=>['Rusak (OOO)','bg-stone-400']] as $kode=>[$label,$warna])
        <div class="flex items-center justify-between py-2 border-b border-stone-50 last:border-0"><span class="flex items-center gap-2 text-sm text-stone-600"><span class="w-2.5 h-2.5 rounded-full {{ $warna }}"></span>{{ $label }}</span><span class="font-bold text-stone-900">{{ $statusKamar[$kode] ?? 0 }}</span></div>
        @endforeach
        <a href="{{ route('kamar.index') }}" class="block text-center text-xs text-amber-600 hover:underline mt-3">Lihat rack kamar →</a>
    </div>
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-4">📈 Okupansi 7 Hari</h2>
        <div class="flex items-end gap-2 h-28">
            @foreach($grafik as $g)
            <div class="flex-1 flex flex-col items-center justify-end gap-1 h-full">
                <span class="text-stone-500" style="font-size:9px">{{ $g['persen'] }}%</span>
                <div class="w-full rounded-t {{ $loop->last ? 'bg-amber-500' : 'bg-stone-300' }}" style="height:{{ max(3, $g['persen']) }}%"></div>
                <span class="text-stone-400" style="font-size:8px">{{ $g['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
