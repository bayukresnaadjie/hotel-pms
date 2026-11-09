@extends('layouts.app')
@use('App\Support\Rp')
@section('title','Reservasi Baru')
@section('subtitle','Ketersediaan dicek langsung saat tanggal dipilih')
@section('content')
@php $masukVal = old('tanggal_masuk', $masuk); $keluarVal = old('tanggal_keluar', $keluar); @endphp
<form action="{{ route('reservasi.store') }}" method="POST" class="grid grid-cols-3 gap-5">@csrf
<div class="col-span-2 space-y-4">

    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">1. Tanggal Menginap</h2>
        <div class="grid grid-cols-3 gap-4 items-end">
            <div><label class="block text-xs font-medium text-stone-600 mb-1">Check-in</label><input type="date" id="masuk" name="tanggal_masuk" value="{{ $masukVal }}" min="{{ today()->toDateString() }}" required class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
            <div><label class="block text-xs font-medium text-stone-600 mb-1">Check-out</label><input type="date" id="keluar" name="tanggal_keluar" value="{{ $keluarVal }}" min="{{ today()->addDay()->toDateString() }}" required class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm"/></div>
            <div class="text-sm text-stone-500 pb-2" id="info-malam">—</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">2. Pilih Tipe Kamar</h2>
        <div class="space-y-2">
            @foreach($tipeList as $t)
            <label class="flex items-center gap-4 border-2 border-stone-100 rounded-xl p-4 cursor-pointer hover:border-amber-300 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 transition" data-tipe="{{ $t->id }}">
                <input type="radio" name="tipe_kamar_id" value="{{ $t->id }}" {{ old('tipe_kamar_id')==$t->id?'checked':'' }} required class="w-4 h-4 text-amber-600"/>
                <div class="flex-1">
                    <div class="font-semibold text-stone-900">{{ $t->nama }} <span class="text-xs font-normal text-stone-400">· {{ $t->jenis_kasur }} · {{ $t->luas_m2 }} m² · maks {{ $t->kapasitas_dewasa }} dewasa, {{ $t->kapasitas_anak }} anak</span></div>
                    <div class="text-xs text-stone-500 mt-0.5">{{ $t->deskripsi }}</div>
                </div>
                <div class="text-right"><div class="font-bold text-stone-900 js-total">{{ $t->harga_dasar_format }}</div><div class="text-xs text-stone-400 js-sub">per malam, dasar</div><div class="text-xs font-semibold js-stok mt-1"></div></div>
            </label>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-semibold mb-3">3. Data Tamu</h2>
        <label class="block text-xs font-medium text-stone-600 mb-1">Tamu terdaftar</label>
        <select name="tamu_id" id="tamu_id" class="w-full border border-stone-200 rounded-lg px-3 py-2.5 text-sm mb-3">
            <option value="">— Tamu baru (isi data di bawah) —</option>
            @foreach($tamuList as $t)<option value="{{ $t->id }}" {{ old('tamu_id',$tamuId)==$t->id?'selected':'' }}>{{ $t->nama }} · {{ $t->telepon }}{{ $t->tipe==='vip'?' ⭐':'' }}</option>@endforeach
        </select>
        <div class="grid grid-cols-3 gap-3" id="tamu-baru">
            <div><label class="block text-xs text-stone-500 mb-1">Nama</label><input name="nama_tamu" value="{{ old('nama_tamu') }}" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
            <div><label class="block text-xs text-stone-500 mb-1">Telepon</label><input name="telepon_tamu" value="{{ old('telepon_tamu') }}" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
            <div><label class="block text-xs text-stone-500 mb-1">Email</label><input type="email" name="email_tamu" value="{{ old('email_tamu') }}" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
        </div>
        <div class="grid grid-cols-4 gap-3 mt-4">
            <div><label class="block text-xs text-stone-500 mb-1">Dewasa</label><input type="number" name="jumlah_dewasa" value="{{ old('jumlah_dewasa',2) }}" min="1" max="10" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
            <div><label class="block text-xs text-stone-500 mb-1">Anak</label><input type="number" name="jumlah_anak" value="{{ old('jumlah_anak',0) }}" min="0" max="10" required class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm"/></div>
            <div class="col-span-2"><label class="block text-xs text-stone-500 mb-1">Sumber Reservasi</label>
                <select name="sumber" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">@foreach(['walk_in'=>'Walk-in','telepon'=>'Telepon','website'=>'Website','ota'=>'OTA (Traveloka/Booking)'] as $v=>$l)<option value="{{ $v }}" {{ old('sumber','telepon')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
        </div>
        <div class="mt-4"><label class="block text-xs text-stone-500 mb-1">Permintaan Khusus</label><textarea name="permintaan_khusus" rows="2" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm">{{ old('permintaan_khusus') }}</textarea></div>
    </div>
</div>

<div class="space-y-4">
    <div class="bg-white rounded-xl border border-stone-200 p-5 sticky top-20">
        <h2 class="text-sm font-semibold mb-3">Ringkasan</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-stone-500">Tipe kamar</span><span id="r-tipe" class="font-medium text-right">—</span></div>
            <div class="flex justify-between"><span class="text-stone-500">Lama menginap</span><span id="r-malam" class="font-medium">—</span></div>
            <div class="flex justify-between border-t border-stone-100 pt-2"><span class="text-stone-500">Total kamar</span><span id="r-total" class="font-bold text-stone-900">—</span></div>
            <div class="text-xs text-stone-400">Belum termasuk service {{ config('hotel.service_persen') }}% dan pajak {{ config('hotel.pajak_persen') }}%.</div>
        </div>
        <div class="border-t border-stone-100 mt-4 pt-4">
            <label class="block text-xs font-medium text-stone-600 mb-1">Deposit (opsional)</label>
            <div class="flex gap-2"><input type="number" name="deposit" id="deposit" value="{{ old('deposit') }}" min="0" class="flex-1 border border-stone-200 rounded-lg px-3 py-2 text-sm"/>
            <button type="button" onclick="isiDeposit()" class="text-xs bg-stone-100 hover:bg-stone-200 px-3 rounded-lg">{{ config('hotel.deposit_persen') }}%</button></div>
            <select name="metode_deposit" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm mt-2">@foreach(['transfer'=>'Transfer Bank','tunai'=>'Tunai','kartu_debit'=>'Kartu Debit','kartu_kredit'=>'Kartu Kredit','qris'=>'QRIS'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
        </div>
        <button class="w-full mt-4 bg-amber-500 hover:bg-amber-600 text-stone-900 font-bold py-3 rounded-xl transition">Simpan Reservasi</button>
        <a href="{{ route('reservasi.index') }}" class="block text-center text-xs text-stone-400 hover:text-stone-600 mt-3">Batal</a>
    </div>
</div>
</form>

<script>
const rp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
const depositPersen = {{ (int) config('hotel.deposit_persen') }};
let hasil = [];

function tanggalValid() {
    const m = document.getElementById('masuk').value, k = document.getElementById('keluar').value;
    return m && k && k > m ? {m, k} : null;
}

async function cek() {
    const t = tanggalValid();
    if (!t) { document.getElementById('info-malam').textContent = 'Tanggal check-out harus setelah check-in'; return; }
    const res = await fetch('{{ route("reservasi.cek") }}?masuk=' + t.m + '&keluar=' + t.k, {headers: {'Accept': 'application/json'}});
    if (!res.ok) return;
    hasil = await res.json();
    document.getElementById('info-malam').textContent = hasil.length ? hasil[0].malam + ' malam' : '';
    hasil.forEach(h => {
        const el = document.querySelector('[data-tipe="' + h.id + '"]');
        if (!el) return;
        const radio = el.querySelector('input[type=radio]');
        el.querySelector('.js-total').textContent = rp(h.total);
        el.querySelector('.js-sub').textContent = 'rata-rata ' + rp(h.rata_rata) + ' / malam';
        const stok = el.querySelector('.js-stok');
        stok.textContent = h.tersedia > 0 ? h.tersedia + ' kamar tersedia' : 'PENUH';
        stok.className = 'text-xs font-semibold js-stok mt-1 ' + (h.tersedia > 0 ? 'text-emerald-600' : 'text-red-500');
        radio.disabled = h.tersedia < 1;
        el.classList.toggle('opacity-50', h.tersedia < 1);
        if (h.tersedia < 1 && radio.checked) radio.checked = false;
    });
    ringkas();
}

function terpilih() {
    const r = document.querySelector('input[name=tipe_kamar_id]:checked');
    return r ? hasil.find(h => String(h.id) === r.value) : null;
}

function ringkas() {
    const h = terpilih();
    document.getElementById('r-tipe').textContent = h ? h.nama : '—';
    document.getElementById('r-malam').textContent = h ? h.malam + ' malam' : '—';
    document.getElementById('r-total').textContent = h ? rp(h.total) : '—';
}

function isiDeposit() {
    const h = terpilih();
    if (h) document.getElementById('deposit').value = Math.round(h.total * depositPersen / 100 / 1000) * 1000;
}

function toggleTamuBaru() {
    const ada = document.getElementById('tamu_id').value !== '';
    document.querySelectorAll('#tamu-baru input').forEach(i => { i.disabled = ada; i.closest('div').classList.toggle('opacity-40', ada); });
}

['masuk', 'keluar'].forEach(id => document.getElementById(id).addEventListener('change', () => {
    const m = document.getElementById('masuk'), k = document.getElementById('keluar');
    if (m.value && (!k.value || k.value <= m.value)) { const d = new Date(m.value); d.setDate(d.getDate() + 1); k.value = d.toISOString().slice(0, 10); }
    k.min = m.value || k.min;
    cek();
}));
document.querySelectorAll('input[name=tipe_kamar_id]').forEach(r => r.addEventListener('change', ringkas));
document.getElementById('tamu_id').addEventListener('change', toggleTamuBaru);
toggleTamuBaru();
cek();
</script>
@endsection
