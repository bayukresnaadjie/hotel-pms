<?php

return [
    'nama'    => 'Grand Nusantara Hotel',
    'alamat'  => 'Jl. Malioboro No. 88, Yogyakarta',
    'telepon' => '(0274) 555-0188',

    // Pajak & service dikenakan di atas tarif: service dari dasar, pajak dari (dasar + service)
    'service_persen' => 10,
    'pajak_persen'   => 10,

    // Malam yang dianggap akhir pekan (Carbon dayOfWeek: 0=Minggu ... 5=Jumat, 6=Sabtu)
    'weekend_days'    => [5, 6],
    'weekend_pengali' => 1.20,

    'jam_checkin'  => '14:00',
    'jam_checkout' => '12:00',

    // Deposit yang disarankan saat membuat reservasi (persen dari total kamar)
    'deposit_persen' => 30,
];
