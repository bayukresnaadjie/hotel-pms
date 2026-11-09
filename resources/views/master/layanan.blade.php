@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Layanan Hotel')
@section('subtitle', $layanan->count() . ' layanan')
@section('content')
@php $kategori = ['fnb'=>'Makanan & Minuman','laundry'=>'Laundry','spa'=>'Spa','transport'=>'Transportasi','lainnya'=>'Lainnya']; @endphp
<form action="{{ route('master.layanan.store') }}" method="POST" class="bg-white rounded-xl border border-stone-200 p-5 mb-5 grid grid-cols-5 gap-3 items-end">@csrf
    <div class="col-span-5 text-sm font-semibold">➕ Tambah Layanan</div>
    <div><label class="block text-xs text-stone-500 mb-1">Kode</label><input name="kode" required maxlength="15" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Nama</label><input name="nama" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Kategori</label><select name="kategori" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">@foreach($kategori as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-stone-500 mb-1">Harga (Rp)</label><input type="number" name="harga" min="1000" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <button class="bg-amber-500 hover:bg-amber-600 text-stone-900 font-semibold text-sm py-2 rounded-lg">Tambah</button>
</form>
<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <table class="w-full text-sm"><thead class="bg-stone-50 border-b border-stone-200"><tr>
        <th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Kode</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Nama</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Kategori</th>
        <th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Harga</th><th class="text-center px-5 py-3 text-xs font-semibold text-stone-400">Aktif</th><th class="px-5 py-3"></th></tr></thead>
    <tbody class="divide-y divide-stone-100">
        @foreach($layanan as $l)
        <tr class="hover:bg-stone-50 {{ $l->aktif ? '' : 'opacity-50' }}">
            <td class="px-5 py-2.5 font-mono text-xs text-amber-700">{{ $l->kode }}<form id="lf{{ $l->id }}" action="{{ route('master.layanan.update',$l) }}" method="POST">@csrf @method('PUT')</form></td>
            <td class="px-5 py-2.5"><input name="nama" form="lf{{ $l->id }}" value="{{ $l->nama }}" required class="w-full border border-stone-200 rounded px-2 py-1 text-sm"/></td>
            <td class="px-5 py-2.5"><select name="kategori" form="lf{{ $l->id }}" class="border border-stone-200 rounded px-2 py-1 text-xs">@foreach($kategori as $v=>$lb)<option value="{{ $v }}" {{ $l->kategori===$v?'selected':'' }}>{{ $lb }}</option>@endforeach</select></td>
            <td class="px-5 py-2.5"><input type="number" name="harga" form="lf{{ $l->id }}" value="{{ (int) $l->harga }}" min="1000" required class="w-28 border border-stone-200 rounded px-2 py-1 text-sm"/></td>
            <td class="px-5 py-2.5 text-center"><input type="checkbox" name="aktif" value="1" form="lf{{ $l->id }}" {{ $l->aktif?'checked':'' }} class="rounded"/></td>
            <td class="px-5 py-2.5 text-right"><button form="lf{{ $l->id }}" class="text-xs bg-stone-800 text-white px-3 py-1 rounded">Simpan</button></td>
        </tr>
        @endforeach
    </tbody></table>
</div>
@endsection
