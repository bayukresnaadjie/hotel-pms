<?php
require __DIR__ . '/stubs.php';
require __DIR__ . '/../../app/Models/TipeKamar.php';
require __DIR__ . '/../../app/Models/Folio.php';
use App\Models\{Folio, TipeKamar};

$gagal = 0; $n = 0;
function cek($nama, $aktual, $harap) { global $gagal, $n; $n++; $ok = $aktual == $harap; if (!$ok) $gagal++;
    echo ($ok ? "  ok   " : "  GAGAL") . " $nama" . ($ok ? '' : " → dapat " . json_encode($aktual) . ", seharusnya " . json_encode($harap)) . "\n"; }

echo "Rumus tagihan (service dari dasar, pajak dari dasar+service):\n";
$k = Folio::hitungKomponen(1000000, 100000);
cek('subtotal 1.000.000, diskon 100.000 → dasar', $k['dasar'], 900000);
cek('service 10%', $k['service'], 90000);
cek('pajak 10% dari (dasar+service)', $k['pajak'], 99000);
cek('total', $k['total'], 1089000);
cek('diskon melebihi subtotal tidak jadi negatif', Folio::hitungKomponen(50000, 80000)['total'], 0);

echo "Tarif per malam:\n";
$t = new TipeKamar(['id' => 1, 'harga_dasar' => 450000]);
cek('Senin (hari biasa)', $t->hargaPerMalam('2026-11-09'), 450000);
cek('Jumat (akhir pekan ×1,2)', $t->hargaPerMalam('2026-11-06'), 540000);
cek('Sabtu (akhir pekan ×1,2)', $t->hargaPerMalam('2026-11-07'), 540000);
cek('Minggu bukan akhir pekan', $t->hargaPerMalam('2026-11-08'), 450000);
$GLOBALS['musim'] = [['2026-11-01', '2026-11-30', 1.5]];
cek('High season ×1,5 pada Senin', $t->hargaPerMalam('2026-11-09'), 675000);
cek('High season × akhir pekan pada Jumat', $t->hargaPerMalam('2026-11-06'), 810000);
$GLOBALS['musim'] = [['2026-11-01', '2026-11-30', 1.5], ['2026-11-05', '2026-11-10', 1.8]];
cek('dua musim tumpang tindih: ambil pengali tertinggi (1,8)', $t->hargaPerMalam('2026-11-09'), 810000);
$GLOBALS['musim'] = [['2026-11-01', '2026-11-30', 0.9]];
cek('promo ×0,9 pada Senin', $t->hargaPerMalam('2026-11-09'), 405000);
$GLOBALS['musim'] = [];
$t2 = new TipeKamar(['id' => 2, 'harga_dasar' => 333333]);
cek('pembulatan ke ribuan (333.333 × 1,2 = 399.999,6 → 400.000)', $t2->hargaPerMalam('2026-11-06'), 400000);

echo "Total menginap (malam = [masuk, keluar)):\n";
$r = $t->rincianTarif('2026-11-06', '2026-11-09');
cek('Jumat→Senin = 3 malam', count($r), 3);
cek('harga: Jum 540k, Sab 540k, Min 450k', array_column($r, 'harga'), [540000, 540000, 450000]);
cek('totalTarif', $t->totalTarif('2026-11-06', '2026-11-09'), 1530000);
cek('masuk = keluar → 0 malam', count($t->rincianTarif('2026-11-06', '2026-11-06')), 0);
cek('1 malam Senin→Selasa', $t->totalTarif('2026-11-09', '2026-11-10'), 450000);

echo "\n$n pengujian, gagal: $gagal\n";
