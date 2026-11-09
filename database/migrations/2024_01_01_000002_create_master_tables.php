<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tipe_kamar', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedTinyInteger('kapasitas_dewasa')->default(2);
            $table->unsignedTinyInteger('kapasitas_anak')->default(1);
            $table->string('jenis_kasur', 50)->nullable();
            $table->unsignedSmallInteger('luas_m2')->nullable();
            $table->decimal('harga_dasar', 12, 2);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('fasilitas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 60)->unique();
            $table->string('icon', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('fasilitas_tipe_kamar', function (Blueprint $table) {
            $table->foreignId('tipe_kamar_id')->constrained('tipe_kamar')->cascadeOnDelete();
            $table->foreignId('fasilitas_id')->constrained('fasilitas')->cascadeOnDelete();
            $table->primary(['tipe_kamar_id','fasilitas_id']);
        });

        Schema::create('kamar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipe_kamar_id')->constrained('tipe_kamar');
            $table->string('nomor', 10)->unique();
            $table->unsignedTinyInteger('lantai')->default(1);
            // siap = bersih & sudah diinspeksi (boleh dijual), kotor, dibersihkan, rusak = out of order
            $table->enum('status_kamar', ['siap','kotor','dibersihkan','rusak'])->default('siap');
            $table->boolean('aktif')->default(true);
            $table->string('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('layanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 15)->unique();
            $table->string('nama');
            $table->enum('kategori', ['fnb','laundry','spa','transport','lainnya'])->default('lainnya');
            $table->decimal('harga', 12, 2);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Tarif musiman: pengali harga untuk rentang tanggal (null tipe_kamar_id = berlaku semua tipe)
        Schema::create('tarif_musiman', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->decimal('pengali', 4, 2)->default(1.00);
            $table->foreignId('tipe_kamar_id')->nullable()->constrained('tipe_kamar')->cascadeOnDelete();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_musiman');
        Schema::dropIfExists('layanan');
        Schema::dropIfExists('kamar');
        Schema::dropIfExists('fasilitas_tipe_kamar');
        Schema::dropIfExists('fasilitas');
        Schema::dropIfExists('tipe_kamar');
    }
};
