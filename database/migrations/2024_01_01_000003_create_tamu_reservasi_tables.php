<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tamu', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('telepon', 20)->nullable();
            $table->enum('jenis_identitas', ['ktp','paspor','sim'])->default('ktp');
            $table->string('nomor_identitas', 30)->nullable()->index();
            $table->text('alamat')->nullable();
            $table->string('kota', 60)->nullable();
            $table->string('negara', 60)->default('Indonesia');
            $table->date('tanggal_lahir')->nullable();
            $table->enum('tipe', ['reguler','vip','blacklist'])->default('reguler');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reservasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking', 12)->unique();
            $table->foreignId('tamu_id')->constrained('tamu');
            $table->foreignId('tipe_kamar_id')->constrained('tipe_kamar');
            $table->foreignId('kamar_id')->nullable()->constrained('kamar')->nullOnDelete();
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar');
            $table->unsignedSmallInteger('jumlah_malam')->default(1);
            $table->unsignedTinyInteger('jumlah_dewasa')->default(1);
            $table->unsignedTinyInteger('jumlah_anak')->default(0);
            $table->enum('sumber', ['walk_in','telepon','website','ota'])->default('walk_in');
            $table->enum('status', ['dikonfirmasi','check_in','check_out','dibatalkan','no_show'])->default('dikonfirmasi');
            $table->decimal('total_kamar', 14, 2)->default(0);
            $table->decimal('deposit', 14, 2)->default(0);
            $table->text('permintaan_khusus')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->string('alasan_batal')->nullable();
            $table->timestamps();

            $table->index(['tipe_kamar_id','tanggal_masuk','tanggal_keluar'], 'idx_rsv_tipe_tanggal');
            $table->index(['kamar_id','tanggal_masuk','tanggal_keluar'], 'idx_rsv_kamar_tanggal');
            $table->index('status', 'idx_rsv_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservasi');
        Schema::dropIfExists('tamu');
    }
};
