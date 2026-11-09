<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolioController;
use App\Http\Controllers\FrontDeskController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\KetersediaanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\NightAuditController;
use App\Http\Controllers\ReservasiController;
use App\Http\Controllers\TamuController;
use App\Http\Middleware\CekRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Pembatas role: kelas middleware CekRole + parameter, mis. $role('admin', 'frontdesk') menghasilkan "CekRole:admin,frontdesk".
// Closure tidak bisa dipakai di Route::middleware() karena Laravel mengubah setiap middleware menjadi string.
$role = fn (string ...$roles): string => CekRole::class.':'.implode(',', $roles);

// ─── AUTH ─────────────────────────────────────────────────────────────────────
Route::get('/login', fn () => Auth::check() ? redirect()->route('dashboard') : view('auth.login'))->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        if (! Auth::user()->aktif) {
            Auth::logout();
            $request->session()->invalidate();

            return back()->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi admin.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        Auth::user()->update(['last_login' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

// ─── TERAUTENTIKASI ───────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () use ($role) {

    // Semua role
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/kamar', [KamarController::class, 'index'])->name('kamar.index');
    Route::get('/ketersediaan', [KetersediaanController::class, 'index'])->name('ketersediaan.index');

    // Front desk & admin: reservasi, check-in/out, tamu
    Route::middleware([$role('admin', 'frontdesk')])->group(function () {
        Route::get('/reservasi', [ReservasiController::class, 'index'])->name('reservasi.index');
        Route::get('/reservasi/create', [ReservasiController::class, 'create'])->name('reservasi.create');
        Route::get('/reservasi/cek-ketersediaan', [ReservasiController::class, 'ketersediaan'])->name('reservasi.cek');
        Route::post('/reservasi', [ReservasiController::class, 'store'])->name('reservasi.store');
        Route::get('/reservasi/{reservasi}', [ReservasiController::class, 'show'])->name('reservasi.show');
        Route::get('/reservasi/{reservasi}/edit', [ReservasiController::class, 'edit'])->name('reservasi.edit');
        Route::put('/reservasi/{reservasi}', [ReservasiController::class, 'update'])->name('reservasi.update');
        Route::patch('/reservasi/{reservasi}/batal', [ReservasiController::class, 'batal'])->name('reservasi.batal');
        Route::patch('/reservasi/{reservasi}/no-show', [ReservasiController::class, 'noShow'])->name('reservasi.no-show');

        Route::get('/front-desk', [FrontDeskController::class, 'index'])->name('frontdesk.index');
        Route::get('/front-desk/{reservasi}/check-in', [FrontDeskController::class, 'checkInForm'])->name('frontdesk.checkin');
        Route::post('/front-desk/{reservasi}/check-in', [FrontDeskController::class, 'checkIn'])->name('frontdesk.checkin.store');
        Route::get('/front-desk/{reservasi}/check-out', [FrontDeskController::class, 'checkOutForm'])->name('frontdesk.checkout');
        Route::post('/front-desk/{reservasi}/check-out', [FrontDeskController::class, 'checkOut'])->name('frontdesk.checkout.store');
        Route::post('/front-desk/{reservasi}/ganti-kamar', [FrontDeskController::class, 'gantiKamar'])->name('frontdesk.ganti-kamar');
        Route::post('/front-desk/{reservasi}/perpanjang', [FrontDeskController::class, 'perpanjang'])->name('frontdesk.perpanjang');

        Route::get('/tamu', [TamuController::class, 'index'])->name('tamu.index');
        Route::get('/tamu/create', [TamuController::class, 'create'])->name('tamu.create');
        Route::post('/tamu', [TamuController::class, 'store'])->name('tamu.store');
        Route::get('/tamu/{tamu}', [TamuController::class, 'show'])->name('tamu.show');
        Route::get('/tamu/{tamu}/edit', [TamuController::class, 'edit'])->name('tamu.edit');
        Route::put('/tamu/{tamu}', [TamuController::class, 'update'])->name('tamu.update');
    });

    // Folio & pembayaran: front desk, finance, admin
    Route::middleware([$role('admin', 'frontdesk', 'finance')])->group(function () {
        Route::get('/folio/{folio}', [FolioController::class, 'show'])->name('folio.show');
        Route::get('/folio/{folio}/invoice', [FolioController::class, 'invoice'])->name('folio.invoice');
        Route::post('/folio/{folio}/item', [FolioController::class, 'tambahItem'])->name('folio.item.store');
        Route::delete('/folio/{folio}/item/{item}', [FolioController::class, 'hapusItem'])->name('folio.item.destroy');
        Route::post('/folio/{folio}/bayar', [FolioController::class, 'bayar'])->name('folio.bayar');
    });

    // Night audit: finance, front desk, admin
    Route::middleware([$role('admin', 'frontdesk', 'finance')])->group(function () {
        Route::get('/night-audit', [NightAuditController::class, 'index'])->name('nightaudit.index');
        Route::post('/night-audit', [NightAuditController::class, 'run'])->name('nightaudit.run');
    });

    // Laporan: finance & admin
    Route::middleware([$role('admin', 'finance')])->group(function () {
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    });

    // Housekeeping: housekeeping, front desk, admin
    Route::middleware([$role('admin', 'frontdesk', 'housekeeping')])->group(function () {
        Route::patch('/kamar/{kamar}/status', [KamarController::class, 'ubahStatus'])->name('kamar.status');

        Route::get('/housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index');
        Route::post('/housekeeping/tugas', [HousekeepingController::class, 'buatTugas'])->name('housekeeping.tugas.store');
        Route::patch('/housekeeping/tugas/{tugas}/tugaskan', [HousekeepingController::class, 'tugaskan'])->name('housekeeping.tugaskan');
        Route::patch('/housekeeping/tugas/{tugas}/mulai', [HousekeepingController::class, 'mulai'])->name('housekeeping.mulai');
        Route::patch('/housekeeping/tugas/{tugas}/selesai', [HousekeepingController::class, 'selesai'])->name('housekeeping.selesai');

        Route::get('/housekeeping/kerusakan', [HousekeepingController::class, 'kerusakan'])->name('housekeeping.kerusakan');
        Route::post('/housekeeping/kerusakan', [HousekeepingController::class, 'simpanKerusakan'])->name('housekeeping.kerusakan.store');
        Route::patch('/housekeeping/kerusakan/{laporan}', [HousekeepingController::class, 'updateKerusakan'])->name('housekeeping.kerusakan.update');
    });

    // Master data: admin
    Route::middleware([$role('admin')])->prefix('master')->name('master.')->group(function () {
        Route::get('/tipe-kamar', [MasterController::class, 'tipe'])->name('tipe');
        Route::put('/tipe-kamar/{tipe}', [MasterController::class, 'updateTipe'])->name('tipe.update');
        Route::get('/kamar', [MasterController::class, 'kamar'])->name('kamar');
        Route::post('/kamar', [MasterController::class, 'simpanKamar'])->name('kamar.store');
        Route::put('/kamar/{kamar}', [MasterController::class, 'updateKamar'])->name('kamar.update');
        Route::get('/layanan', [MasterController::class, 'layanan'])->name('layanan');
        Route::post('/layanan', [MasterController::class, 'simpanLayanan'])->name('layanan.store');
        Route::put('/layanan/{layanan}', [MasterController::class, 'updateLayanan'])->name('layanan.update');
        Route::get('/tarif-musiman', [MasterController::class, 'tarif'])->name('tarif');
        Route::post('/tarif-musiman', [MasterController::class, 'simpanTarif'])->name('tarif.store');
        Route::delete('/tarif-musiman/{tarif}', [MasterController::class, 'hapusTarif'])->name('tarif.destroy');
    });
});
