@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Check-in')
@section('subtitle', $reservasi->kode_booking . ' · ' . $reservasi->tamu->nama)
@section('content')
@php $tamu = $reservasi->tamu; $butuhId = empty($tamu->nomor_identitas); @endphp
<form action="{{ route('frontdesk.checkin.store',$reservasi) }}" method="POST" class="grid grid-cols-3 gap-5">@csrf
<div class="col-span-2 space-y-4">
    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">Pilih Kamar — {{ $reservasi->tipeKamar->nama }}</h2>
        @if($kamarSiap->isEmpty())
            <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-4 text-sm text-red-700">
                <div class="font-semibold mb-1">Tidak ada kamar {{ $reservasi->tipeKamar->nama }} yang siap.</div>
                @if($kamarBelum>0)<div>{{ $kamarBelum }} kamar tipe ini masih kotor atau sedang dibersihkan. <a href="{{ route('housekeeping.index') }}" class="underline font-medium">Cek housekeeping</a>.</div>@else<div>Semua kamar tipe ini terisi atau tidak tersedia.</div>@endif
            </div>
        @else
            <div class="grid grid-cols-4 gap-3">
                @foreach($kamarSiap as $k)
                <label class="border-2 border-stone-100 rounded-xl p-3 text-center cursor-pointer hover:border-amber-300 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 transition">
                    <input type="radio" name="kamar_id" value="{{ $k->id }}" {{ old('kamar_id')==$k->id?'checked':'' }} required class="sr-only"/>
                    <div class="text-xl font-bold text-stone-900">{{ $k->nomor }}</div><div class="text-xs text-stone-400">Lantai {{ $k->lantai }}</div>
                    @if($k->catatan)<div class="text-xs text-amber-700 mt-1">{{ $k->catatan }}</div>@endif
                </label>
                @endforeach
            </div>
            @if($reservasi->permintaan_khusus)<div class="mt-3 text-xs text-amber-800 bg-amber-50 rounded-lg px-3 py-2">📝 Permintaan tamu: {{ $reservasi->permintaan_khusus }}</div>@endif
        @endif
    </div>

    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">Identitas Tamu</h2>
        @if(!$butuhId)<div class="text-sm text-stone-600 mb-3">{{ strtoupper($tamu->jenis_identitas) }} <b>{{ $tamu->nomor_identitas }}</b> sudah tercatat. Cocokkan dengan dokumen asli tamu.</div>@endif
        <div class="grid grid-cols-3 gap-3">
            <div><label class="block text-xs text-stone-500 mb-1">Jenis</label><select name="jenis_identitas" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">@foreach(['ktp'=>'KTP','paspor'=>'Paspor','sim'=>'SIM'] as $v=>$l)<option value="{{ $v }}" {{ old('jenis_identitas',$tamu->jenis_identitas)===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
            <div class="col-span-2"><label class="block text-xs text-stone-500 mb-1">Nomor Identitas {{ $butuhId ? '*' : '(ubah bila perlu)' }}</label><input name="nomor_identitas" value="{{ old('nomor_identitas') }}" placeholder="{{ $butuhId ? 'Wajib diisi' : $tamu->nomor_identitas }}" {{ $butuhId ? 'required' : '' }} class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
        </div>
    </div>
</div>

<div class="space-y-4">
    <div class="bg-white rounded-xl border border-stone-200 p-5 text-sm space-y-2">
        <h2 class="font-semibold mb-1">Ringkasan Reservasi</h2>
        <div class="flex justify-between"><span class="text-stone-500">Tamu</span><span class="font-medium">{{ $tamu->nama }} {{ $tamu->tipe_badge['icon'] }}</span></div>
        <div class="flex justify-between"><span class="text-stone-500">Menginap</span><span class="font-medium">{{ $reservasi->tanggal_masuk->translatedFormat('d M') }} → {{ $reservasi->tanggal_keluar->translatedFormat('d M Y') }}</span></div>
        <div class="flex justify-between"><span class="text-stone-500">Lama</span><span class="font-medium">{{ $reservasi->jumlah_malam }} malam</span></div>
        <div class="flex justify-between"><span class="text-stone-500">Tamu</span><span class="font-medium">{{ $reservasi->jumlah_dewasa }} dewasa{{ $reservasi->jumlah_anak>0 ? ', '.$reservasi->jumlah_anak.' anak' : '' }}</span></div>
        <div class="flex justify-between"><span class="text-stone-500">Total kamar</span><span class="font-medium">{{ Rp::fmt($reservasi->total_kamar) }}</span></div>
        <div class="flex justify-between border-t border-stone-100 pt-2"><span class="text-stone-500">Deposit dibayar</span><span class="font-bold text-emerald-600">{{ Rp::fmt($reservasi->folio?->terbayar ?? 0) }}</span></div>
    </div>
    <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition {{ $kamarSiap->isEmpty() ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $kamarSiap->isEmpty() ? 'disabled' : '' }}>🛬 Selesaikan Check-in</button>
    <a href="{{ route('frontdesk.index') }}" class="block text-center text-xs text-stone-400 hover:text-stone-600">Kembali ke papan front desk</a>
</div>
</form>
@endsection
