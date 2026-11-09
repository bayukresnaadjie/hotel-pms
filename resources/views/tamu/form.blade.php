@extends('layouts.app')
@php $edit = $tamu->exists; @endphp
@section('title', $edit ? 'Ubah Data Tamu' : 'Tamu Baru')
@section('content')
<div class="max-w-2xl bg-white rounded-xl border border-stone-200 p-6">
<form action="{{ $edit ? route('tamu.update',$tamu) : route('tamu.store') }}" method="POST" class="grid grid-cols-2 gap-4">@csrf @if($edit) @method('PUT') @endif
    <div class="col-span-2"><label class="block text-xs font-medium text-stone-600 mb-1">Nama Lengkap *</label><input name="nama" value="{{ old('nama',$tamu->nama) }}" required class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Telepon</label><input name="telepon" value="{{ old('telepon',$tamu->telepon) }}" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Email</label><input type="email" name="email" value="{{ old('email',$tamu->email) }}" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Jenis Identitas</label><select name="jenis_identitas" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm">@foreach(['ktp'=>'KTP','paspor'=>'Paspor','sim'=>'SIM'] as $v=>$l)<option value="{{ $v }}" {{ old('jenis_identitas',$tamu->jenis_identitas ?? 'ktp')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Nomor Identitas</label><input name="nomor_identitas" value="{{ old('nomor_identitas',$tamu->nomor_identitas) }}" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Kota</label><input name="kota" value="{{ old('kota',$tamu->kota) }}" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Negara *</label><input name="negara" value="{{ old('negara',$tamu->negara ?? 'Indonesia') }}" required class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir',$tamu->tanggal_lahir?->toDateString()) }}" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
    <div><label class="block text-xs font-medium text-stone-600 mb-1">Status Tamu</label><select name="tipe" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm">@foreach(['reguler'=>'Reguler','vip'=>'VIP ⭐','blacklist'=>'Blacklist ⛔'] as $v=>$l)<option value="{{ $v }}" {{ old('tipe',$tamu->tipe ?? 'reguler')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
    <div class="col-span-2"><label class="block text-xs font-medium text-stone-600 mb-1">Alamat</label><textarea name="alamat" rows="2" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm">{{ old('alamat',$tamu->alamat) }}</textarea></div>
    <div class="col-span-2"><label class="block text-xs font-medium text-stone-600 mb-1">Catatan (preferensi, alergi, dll)</label><textarea name="catatan" rows="2" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm">{{ old('catatan',$tamu->catatan) }}</textarea></div>
    <div class="col-span-2 flex gap-3 pt-2 border-t border-stone-100"><a href="{{ $edit ? route('tamu.show',$tamu) : route('tamu.index') }}" class="flex-1 text-center bg-stone-100 text-stone-700 text-sm py-2.5 rounded-lg">Batal</a><button class="flex-1 bg-amber-500 hover:bg-amber-600 text-stone-900 font-semibold text-sm py-2.5 rounded-lg">{{ $edit ? 'Simpan' : 'Tambah Tamu' }}</button></div>
</form>
</div>
@endsection
