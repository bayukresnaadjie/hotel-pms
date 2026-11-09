@extends('layouts.app')
@section('title','Daftar Kamar')
@section('subtitle', $kamar->count() . ' kamar terdaftar')
@section('content')
<form action="{{ route('master.kamar.store') }}" method="POST" class="bg-white rounded-xl border border-stone-200 p-5 mb-5 grid grid-cols-5 gap-3 items-end">@csrf
    <div class="col-span-5 text-sm font-semibold">➕ Tambah Kamar</div>
    <div><label class="block text-xs text-stone-500 mb-1">Nomor</label><input name="nomor" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Tipe</label><select name="tipe_kamar_id" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">@foreach($tipeList as $t)<option value="{{ $t->id }}">{{ $t->nama }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-stone-500 mb-1">Lantai</label><input type="number" name="lantai" value="1" min="1" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <div><label class="block text-xs text-stone-500 mb-1">Catatan</label><input name="catatan" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
    <button class="bg-amber-500 hover:bg-amber-600 text-stone-900 font-semibold text-sm py-2 rounded-lg">Tambah</button>
</form>
<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <table class="w-full text-sm"><thead class="bg-stone-50 border-b border-stone-200"><tr>
        <th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Nomor</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Tipe</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Lantai</th>
        <th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Status</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Catatan</th><th class="text-center px-5 py-3 text-xs font-semibold text-stone-400">Aktif</th><th class="px-5 py-3"></th></tr></thead>
    <tbody class="divide-y divide-stone-100">
        @foreach($kamar as $k)
        <tr class="hover:bg-stone-50 {{ $k->aktif ? '' : 'opacity-50' }}">
            <td class="px-5 py-2.5 font-bold text-stone-900">{{ $k->nomor }}<form id="kf{{ $k->id }}" action="{{ route('master.kamar.update',$k) }}" method="POST">@csrf @method('PUT')</form></td>
            <td class="px-5 py-2.5"><select name="tipe_kamar_id" form="kf{{ $k->id }}" class="border border-stone-200 rounded px-2 py-1 text-xs">@foreach($tipeList as $t)<option value="{{ $t->id }}" {{ $k->tipe_kamar_id===$t->id?'selected':'' }}>{{ $t->nama }}</option>@endforeach</select></td>
            <td class="px-5 py-2.5"><input type="number" name="lantai" form="kf{{ $k->id }}" value="{{ $k->lantai }}" min="1" class="w-16 border border-stone-200 rounded px-2 py-1 text-xs"/></td>
            <td class="px-5 py-2.5"><span class="text-xs px-2 py-0.5 rounded-full {{ $k->status_badge['class'] }}">{{ $k->status_badge['label'] }}</span></td>
            <td class="px-5 py-2.5"><input name="catatan" form="kf{{ $k->id }}" value="{{ $k->catatan }}" class="w-full border border-stone-200 rounded px-2 py-1 text-xs"/></td>
            <td class="px-5 py-2.5 text-center"><input type="checkbox" name="aktif" value="1" form="kf{{ $k->id }}" {{ $k->aktif?'checked':'' }} class="rounded"/></td>
            <td class="px-5 py-2.5 text-right"><button form="kf{{ $k->id }}" class="text-xs bg-stone-800 text-white px-3 py-1 rounded">Simpan</button></td>
        </tr>
        @endforeach
    </tbody></table>
</div>
@endsection
