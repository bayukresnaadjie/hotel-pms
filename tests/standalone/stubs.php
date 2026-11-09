<?php
namespace Illuminate\Database\Eloquent { #[\AllowDynamicProperties] class Model { public function __construct(array $a = []) { foreach ($a as $k => $v) { $this->$k = $v; } } } }
namespace Illuminate\Support { class Str {} }
namespace Carbon {
    class Carbon {
        public \DateTime $dt;
        public function __construct($s = 'now') { $this->dt = new \DateTime($s); }
        public static function parse($s) { return $s instanceof self ? clone $s : new self(is_string($s) ? $s : 'now'); }
        public function startOfDay() { $this->dt->setTime(0, 0); return $this; }
        public function copy() { return clone $this; }
        public function __clone() { $this->dt = clone $this->dt; }
        public function addDay() { $this->dt->modify('+1 day'); return $this; }
        public function lt($o) { return $this->dt < $o->dt; }
        public function toDateString() { return $this->dt->format('Y-m-d'); }
        public function __get($k) { if ($k === 'dayOfWeek') return (int) $this->dt->format('w'); }
    }
}
namespace App\Models {
    // Stub tarif musiman: mengembalikan pengali tertinggi dari daftar $GLOBALS['musim'] yang menutupi tanggal
    class TarifMusiman {
        public static function berlakuPada($tgl, $tipeId = null) {
            $d = \Carbon\Carbon::parse($tgl)->toDateString();
            $cocok = array_filter($GLOBALS['musim'], fn($m) => $m[0] <= $d && $m[1] >= $d);
            return new class(array_column($cocok, 2)) { public function __construct(public array $p) {} public function max($c) { return $this->p ? max($this->p) : null; } };
        }
    }
}
namespace {
    $CFG = ['hotel.weekend_days' => [5, 6], 'hotel.weekend_pengali' => 1.2, 'hotel.service_persen' => 10, 'hotel.pajak_persen' => 10];
    function config($k, $d = null) { global $CFG; return $CFG[$k] ?? $d; }
    $musim = [];
}
