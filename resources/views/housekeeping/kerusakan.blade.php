@extends('layouts.app')
@section('title','Laporan Kerusakan')
@section('subtitle', $laporan->where('status','!=','selesai')->count() . ' laporan terbuka')
@section('content')
<div class="grid grid-cols-3 gap-5">
<div>
    <form action="{{ route('housekeeping.kerusakan.store') }}" method="POST" class="bg-white rounded-xl border border-stone-200 p-5 space-y-3 sticky top-20">@csrf
        <h2 class="text-sm font-semibold">Laporkan Kerusakan</h2>
        <div><label class="block text-xs text-stone-500 mb-1">Kamar</label><select name="kamar_id" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">@foreach($kamarList as $k)<option value="{{ $k->id }}">{{ $k->nomor }}</option>@endforeach</select></div>
        <div><label class="block text-xs text-stone-500 mb-1">Masalah</label><input name="judul" required placeholder="mis. AC tidak dingin" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
        <div><label class="block text-xs text-stone-500 mb-1">Detail</label><textarea name="deskripsi" rows="3" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"></textarea></div>
        <div><label class="block text-xs text-stone-500 mb-1">Prioritas</label><select name="prioritas" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"><option value="rendah">Rendah</option><option value="sedang" selected>Sedang</option><option value="tinggi">Tinggi</option></select></div>
        <label class="flex items-start gap-2 text-xs text-stone-600"><input type="checkbox" name="kamar_tidak_bisa_dipakai" value="1" class="rounded mt-0.5"/> <span>Kamar tidak bisa dipakai. Kamar ditandai <b>rusak</b> dan keluar dari stok jual sampai perbaikan selesai.</span></label>
        <button class="w-full bg-amber-500 hover:bg-amber-600 text-stone-900 font-semibold text-sm py-2.5 rounded-lg">Kirim Laporan</button>
    </form>
</div>
<div class="col-span-2 bg-white rounded-xl border border-stone-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-stone-100"><h2 class="text-sm font-semibold">Daftar Laporan</h2></div>
    <div class="divide-y divide-stone-50">
        @forelse($laporan as $l)
        <div class="px-5 py-4 {{ $l->status==='selesai' ? 'opacity-60' : '' }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap"><span class="font-bold text-stone-900">Kamar {{ $l->kamar->nomor }}</span><span class="text-sm text-stone-800">{{ $l->judul }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $l->prioritas_badge['class'] }}">{{ $l->prioritas_badge['label'] }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $l->status_badge['class'] }}">{{ $l->status_badge['label'] }}</span>
                    @if($l->kamar_tidak_bisa_dipakai)<span class="text-xs px-2 py-0.5 rounded-full bg-stone-800 text-white">Kamar diblokir</span>@endif</div>
                    @if($l->deskripsi)<div class="text-xs text-stone-500 mt-1">{{ $l->deskripsi }}</div>@endif
                    <div class="text-xs text-stone-400 mt-1">{{ $l->pelapor?->name ?? '-' }} · {{ $l->created_at->translatedFormat('d M Y H:i') }}@if($l->diselesaikan_at) · selesai {{ $l->diselesaikan_at->translatedFormat('d M') }}@endif</div>
                </div>
                @if($l->status!=='selesai')
                <div class="flex gap-1.5 flex-shrink-0">
                    @if($l->status==='dilaporkan')<form action="{{ route('housekeeping.kerusakan.update',$l) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="diproses"/><button class="text-xs bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1.5 rounded-lg">Proses</button></form>@endif
                    <form action="{{ route('housekeeping.kerusakan.update',$l) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="selesai"/><button class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg">Selesai</button></form>
                </div>
                @endif
            </div>
        </div>
        @empty<div class="px-5 py-12 text-center text-sm text-stone-400">Belum ada laporan kerusakan</div>@endforelse
    </div>
</div>
</div>
@endsection
