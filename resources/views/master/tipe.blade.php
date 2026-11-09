@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Tipe Kamar')
@section('subtitle','Harga dasar per malam. Pengali akhir pekan dan musiman diterapkan di atasnya')
@section('content')
<div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-900 mb-4">Perubahan harga berlaku untuk reservasi baru dan biaya kamar yang diposting berikutnya. Biaya yang sudah masuk folio tidak berubah.</div>
<div class="space-y-4">
@foreach($tipe as $t)
<form action="{{ route('master.tipe.update',$t) }}" method="POST" class="bg-white rounded-xl border border-stone-200 p-5 grid grid-cols-6 gap-4 items-end">@csrf @method('PUT')
    <div class="col-span-6 flex items-center justify-between"><div><span class="font-mono text-xs bg-stone-100 px-2 py-0.5 rounded">{{ $t->kode }}</span> <span class="text-xs text-stone-400">{{ $t->kamar_count }} kamar · {{ $t->jenis_kasur }} · {{ $t->luas_m2 }} m² · {{ $t->kapasitas_dewasa }} dewasa, {{ $t->kapasitas_anak }} anak</span></div>
        <div class="flex gap-1 flex-wrap justify-end">@foreach($t->fasilitas as $f)<span class="text-xs bg-stone-100 text-stone-600 px-2 py-0.5 rounded-full">{{ $f->icon }} {{ $f->nama }}</span>@endforeach</div></div>
    <div class="col-span-2"><label class="block text-xs text-stone-500 mb-1">Nama</label><input name="nama" value="{{ $t->nama }}" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Harga dasar (Rp)</label><input type="number" name="harga_dasar" value="{{ (int) $t->harga_dasar }}" min="50000" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div class="col-span-2"><label class="block text-xs text-stone-500 mb-1">Deskripsi</label><input name="deskripsi" value="{{ $t->deskripsi }}" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div class="flex items-center gap-3"><label class="flex items-center gap-1.5 text-xs text-stone-600"><input type="checkbox" name="aktif" value="1" {{ $t->aktif?'checked':'' }} class="rounded"/> Aktif</label><button class="bg-stone-800 hover:bg-stone-900 text-white text-sm px-4 py-2 rounded-lg">Simpan</button></div>
</form>
@endforeach
</div>
@endsection
