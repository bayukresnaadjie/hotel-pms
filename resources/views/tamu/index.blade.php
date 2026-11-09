@extends('layouts.app')
@section('title','Data Tamu')
@section('subtitle', $tamu->total() . ' tamu')
@section('header-action')<a href="{{ route('tamu.create') }}" class="bg-amber-500 hover:bg-amber-600 text-stone-900 text-sm font-semibold px-4 py-2 rounded-lg">+ Tamu Baru</a>@endsection
@section('content')
<form method="GET" class="bg-white rounded-xl border border-stone-200 p-4 mb-4 flex gap-3">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama, telepon, email, atau nomor identitas" class="flex-1 border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
    <select name="tipe" class="border border-stone-200 rounded-lg px-3 py-2 text-sm"><option value="">Semua tipe</option>@foreach(['reguler','vip','blacklist'] as $t)<option value="{{ $t }}" {{ request('tipe')===$t?'selected':'' }}>{{ ucfirst($t) }}</option>@endforeach</select>
    <button class="bg-stone-800 text-white text-sm px-5 py-2 rounded-lg">Cari</button>
</form>
<div class="bg-white rounded-xl border border-stone-200 overflow-hidden"><table class="w-full text-sm"><thead class="bg-stone-50 border-b border-stone-200"><tr>
    <th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Tamu</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Kontak</th><th class="text-left px-5 py-3 text-xs font-semibold text-stone-400">Asal</th>
    <th class="text-center px-5 py-3 text-xs font-semibold text-stone-400">Reservasi</th><th class="text-center px-5 py-3 text-xs font-semibold text-stone-400">Tipe</th><th class="px-5 py-3"></th></tr></thead>
<tbody class="divide-y divide-stone-100">
    @forelse($tamu as $t)
    <tr class="hover:bg-stone-50"><td class="px-5 py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center text-xs font-bold">{{ $t->inisial }}</div><div class="font-medium text-stone-900">{{ $t->nama }}</div></div></td>
    <td class="px-5 py-3 text-xs text-stone-600">{{ $t->telepon }}<div class="text-stone-400">{{ $t->email }}</div></td>
    <td class="px-5 py-3 text-xs text-stone-600">{{ $t->kota }}{{ $t->kota ? ', ' : '' }}{{ $t->negara }}</td>
    <td class="px-5 py-3 text-center font-bold">{{ $t->total_reservasi }}</td>
    <td class="px-5 py-3 text-center"><span class="text-xs px-2.5 py-1 rounded-full {{ $t->tipe_badge['class'] }}">{{ $t->tipe_badge['icon'] }} {{ $t->tipe_badge['label'] }}</span></td>
    <td class="px-5 py-3 text-right"><a href="{{ route('tamu.show',$t) }}" class="text-xs text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1 rounded-lg">Profil</a></td></tr>
    @empty<tr><td colspan="6" class="px-5 py-12 text-center text-sm text-stone-400">Tidak ada tamu</td></tr>@endforelse
</tbody></table>@if($tamu->hasPages())<div class="px-5 py-3 border-t">{{ $tamu->links() }}</div>@endif</div>
@endsection
