# 🏨 Grand Nusantara PMS — Hotel Management System

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind-CDN-06B6D4?style=flat&logo=tailwindcss&logoColor=white)

Property Management System untuk hotel: reservasi dengan pengecekan stok per malam, papan front desk (check-in, check-out, pindah kamar, perpanjang), folio tamu dengan service dan pajak, night audit, housekeeping, serta laporan okupansi, ADR, dan RevPAR.

---

## ✨ Fitur Utama

### 📒 Reservasi
- Cek ketersediaan **langsung saat tanggal dipilih** (AJAX), lengkap dengan total tarif per tipe kamar
- **Anti double booking**: baris tipe kamar dikunci (`lockForUpdate`) di dalam transaksi, sehingga dua booking bersamaan tidak bisa sama-sama lolos cek stok
- Validasi kapasitas dewasa/anak per tipe kamar
- Deposit (saran 30%), pembatalan dengan opsi refund, tandai no-show, ubah reservasi
- Tamu blacklist ditolak otomatis

### 🛎️ Front Desk
- Papan kerja harian: kedatangan, keberangkatan, tamu in-house, kamar siap jual per tipe
- **Check-in**: pilih kamar yang bersih dan kosong, identitas tamu wajib terisi
- **Check-out**: proyeksi tagihan akhir, malam yang belum tercatat diposting otomatis, **check-out lebih awal** melepas stok, **kelebihan inap** ikut ditagihkan, kelebihan deposit direfund
- Pindah kamar (kamar lama otomatis kotor + masuk antrean housekeeping) dan perpanjang menginap dengan cek bentrok

### 🧾 Folio & Pembayaran
- Biaya kamar per malam, layanan (sarapan, laundry, spa, dll), biaya manual, diskon (khusus admin/finance)
- Pembayaran multi-metode (tunai, kartu, transfer, QRIS), deposit, refund
- Invoice siap cetak / simpan PDF dari browser

### 🌙 Night Audit
- Memposting biaya kamar malam itu ke folio semua tamu in-house dalam satu transaksi
- Tidak bisa dijalankan dua kali pada hari yang sama
- Daftar tamu yang belum datang untuk diputuskan (tunggu atau no-show)

### 🧹 Housekeeping & Kerusakan
- Status kamar: siap, kotor, dibersihkan, rusak (out of order) dengan riwayat perubahan
- Tugas pembersihan (check-out, stayover), penugasan petugas, mulai / selesai
- Kamar otomatis menjadi **siap** setelah pembersihan selesai
- Laporan kerusakan yang bisa memblokir kamar; **peringatan** bila stok tipe kamar jadi kurang dari reservasi mendatang

### 📅 Ketersediaan
- Kalender sisa kamar per tipe selama 14 malam (merah = penuh) beserta harga tiap malam
- Tape chart kamar: tamu yang sudah ditempatkan, per kamar

### 📈 Laporan
- Okupansi, **ADR**, **RevPAR**, pendapatan kamar vs layanan, diskon
- Per tipe kamar, per sumber reservasi, layanan terlaris, grafik harian
- Proyeksi okupansi 14 malam ke depan

---

## 🧮 Aturan Bisnis

**Stok per malam** memakai interval setengah terbuka. Tamu yang check-out hari X tidak menghalangi tamu yang check-in hari X:

```
tersedia(tipe, masuk, keluar) =
    jumlah kamar aktif tipe itu (di luar yang rusak)
  − reservasi aktif tipe itu dengan:  r.masuk < keluar  DAN  r.keluar > masuk
```

**Tarif per malam** = harga dasar × pengali musiman (tertinggi bila tumpang tindih) × pengali akhir pekan (Jumat dan Sabtu, 1,2×), dibulatkan ke ribuan.

**Tagihan:**

```
dasar   = Σ biaya − diskon
service = 10% × dasar
pajak   = 10% × (dasar + service)
total   = dasar + service + pajak
```

Persentase dan jam check-in/out ada di `config/hotel.php`.

---

## 👥 Role & Akses

| Role | Akses |
|---|---|
| **Admin** | Semua fitur + master data (tipe kamar, kamar, layanan, tarif musiman) |
| **Front Desk** | Reservasi, check-in/out, tamu, folio, night audit, housekeeping |
| **Housekeeping** | Status kamar, tugas pembersihan, laporan kerusakan |
| **Finance** | Folio, pembayaran, diskon, refund, night audit, laporan |

---

## 🛠️ Tech Stack

Laravel 11 (PHP 8.2+) · MySQL 8 · Blade + Tailwind CSS (CDN) · Auth bawaan Laravel (session). Pembatasan role memakai closure middleware di `routes/web.php`, tanpa mengubah `bootstrap/app.php`.

---

## ⚙️ Cara Install

```bash
composer create-project laravel/laravel hotel-pms
cd hotel-pms
# salin seluruh isi repository ini ke folder proyek (timpa file yang sama)
cp .env.hotel .env        # atau edit .env: DB_DATABASE=hotel_pms, SESSION_DRIVER=file, QUEUE_CONNECTION=sync, CACHE_STORE=file
# buat database: CREATE DATABASE hotel_pms;
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Buka **http://127.0.0.1:8000**

---

## 🔑 Akun Demo

Password semua akun: `password`

| Email | Role |
|---|---|
| admin@hotel.test | Admin |
| frontdesk@hotel.test | Front Desk |
| dewi@hotel.test | Front Desk |
| hk1@hotel.test, hk2@hotel.test | Housekeeping |
| finance@hotel.test | Finance |

**Data demo** dibuat dengan tanggal relatif terhadap hari ini, sehingga selalu terisi: 22 kamar (4 tipe), 16 tamu, 44 reservasi (riwayat, in-house, kedatangan hari ini, booking mendatang, no-show, pembatalan), folio lengkap dengan pembayaran, night audit historis, tugas housekeeping, dan laporan kerusakan.

**Skenario yang bisa dicoba:**
1. `frontdesk@hotel.test` → **Papan Front Desk** → check-in tamu kedatangan hari ini, pilih kamar
2. **Reservasi Baru**, pilih tanggal 3–5 hari ke depan: *Executive Suite* tampil **PENUH** (sengaja disediakan sold-out)
3. Check-out tamu pada daftar keberangkatan: proyeksi tagihan muncul, lunasi sisa, folio tertutup, kamar menjadi kotor
4. `hk1@hotel.test` → **Housekeeping** → mulai dan selesaikan pembersihan: kamar kembali **siap**
5. `finance@hotel.test` → **Night Audit** → jalankan → lihat **Laporan** (okupansi, ADR, RevPAR)
6. Tandai kamar rusak lewat **Laporan Kerusakan**, lalu lihat dampaknya pada stok

---

## 🗄️ Struktur Database (15 tabel)

```
users · tipe_kamar · fasilitas · fasilitas_tipe_kamar · kamar · layanan · tarif_musiman
tamu · reservasi
folio · folio_item · pembayaran · night_audit
housekeeping_task · status_kamar_log · laporan_kerusakan
```

## 📁 Struktur Penting

```
app/Models/            ← logika inti: TipeKamar (stok, tarif), Folio (tagihan), Kamar, Reservasi
app/Http/Controllers/  ← FrontDesk, Reservasi, Folio, NightAudit, Housekeeping, Laporan, Master, ...
app/Support/Rp.php     ← format Rupiah
config/hotel.php       ← nama hotel, pajak, service, akhir pekan, jam check-in/out
database/seeders/      ← data demo (tanggal relatif)
resources/views/       ← 25 halaman Blade
tests/standalone/      ← uji logika murni tanpa Laravel
```

---

## ✅ Pengujian

Logika perhitungan murni (rumus tagihan, tarif akhir pekan / musiman, pembulatan, jumlah malam) punya uji mandiri yang tidak membutuhkan Laravel maupun database:

```bash
php tests/standalone/uji_logika.php
```

Hasil: **19 pengujian, 0 gagal**. Uji ini memuat file model asli dengan stub kecil, sehingga yang diuji adalah kode produksi.

---

## ⚠️ Batasan Saat Ini

- Belum ada tes otomatis tingkat aplikasi (PHPUnit) untuk alur HTTP dan database
- Satu reservasi = satu kamar; booking grup belum didukung
- Belum ada manajemen user di UI (akun dibuat lewat seeder)
- Kamar baru ditetapkan saat check-in, sehingga tape chart hanya menampilkan tamu yang sudah ditempatkan
- Rute login memakai closure, jadi `php artisan route:cache` tidak bisa dipakai tanpa memindahkannya ke controller
- Tampilan memakai Tailwind via CDN

## 🔮 Rencana Pengembangan

- [ ] Booking grup (beberapa kamar dalam satu reservasi) dan rate plan
- [ ] Manajemen user dan hak akses dari UI
- [ ] Integrasi channel manager / OTA dan payment gateway
- [ ] Tes otomatis PHPUnit untuk alur check-in/out dan night audit
- [ ] Ekspor laporan ke Excel / PDF server-side
- [ ] Notifikasi (kedatangan VIP, kamar siap, tagihan melewati batas)

---

## 👨‍💻 Developer

**Bayu Kresna Adjie**
- GitHub: [@bayukresnaadjie](https://github.com/bayukresnaadjie)
- LinkedIn: [bayu-kresna-adjie](https://www.linkedin.com/in/bayu-kresna-adjie-a883301ab)
- Lokasi: Yogyakarta, Indonesia

## 📄 Lisensi

Dibuat untuk keperluan portofolio dan pembelajaran.
