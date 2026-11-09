@extends('layouts.app')
@section('title','Ubah Reservasi')
@section('subtitle', $reservasi->kode_booking . ' · ' . $reservasi->tamu->nama)
@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-stone-200 p-6">
<form action="{{ route('reservasi.update',$reservasi) }}" method="POST" class="space-y-4">@csrf @method('PUT')
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Tipe Kamar</label>
        <select name="tipe_kamar_id" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm">@foreach($tipeList as $t)<option value="{{ $t->id }}" {{ old('tipe_kamar_id',$reservasi->tipe_kamar_id)==$t->id?'selected':'' }}>{{ $t->nama }} — {{ $t->harga_dasar_format }}</option>@endforeach</select></div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-xs font-medium text-stone-600 mb-1">Check-in</label><input type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk',$reservasi->tanggal_masuk->toDateString()) }}" min="{{ today()->toDateString() }}" required class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
        <div><label class="block text-xs font-medium text-stone-600 mb-1">Check-out</label><input type="date" name="tanggal_keluar" value="{{ old('tanggal_keluar',$reservasi->tanggal_keluar->toDateString()) }}" required class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
        <div><label class="block text-xs font-medium text-stone-600 mb-1">Dewasa</label><input type="number" name="jumlah_dewasa" value="{{ old('jumlah_dewasa',$reservasi->jumlah_dewasa) }}" min="1" max="10" required class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
        <div><label class="block text-xs font-medium text-stone-600 mb-1">Anak</label><input type="number" name="jumlah_anak" value="{{ old('jumlah_anak',$reservasi->jumlah_anak) }}" min="0" max="10" required class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
    </div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Permintaan Khusus</label><textarea name="permintaan_khusus" rows="2" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm">{{ old('permintaan_khusus',$reservasi->permintaan_khusus) }}</textarea></div>
    <p class="text-xs text-stone-400">Perubahan tanggal atau tipe akan menghitung ulang total kamar dan mengecek stok kamar.</p>
    <div class="flex gap-3 pt-2 border-t border-stone-100"><a href="{{ route('reservasi.show',$reservasi) }}" class="flex-1 text-center bg-stone-100 text-stone-700 text-sm py-2.5 rounded-lg">Batal</a><button class="flex-1 bg-amber-500 hover:bg-amber-600 text-stone-900 font-semibold text-sm py-2.5 rounded-lg">Simpan Perubahan</button></div>
</form>
</div>
@endsection
