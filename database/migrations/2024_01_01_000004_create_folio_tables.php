<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('folio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservasi_id')->unique()->constrained('reservasi')->cascadeOnDelete();
            $table->string('nomor_folio', 20)->unique();
            $table->enum('status', ['terbuka','tertutup'])->default('terbuka');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('diskon', 14, 2)->default(0);
            $table->decimal('service', 14, 2)->default(0);
            $table->decimal('pajak', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('terbayar', 14, 2)->default(0);
            $table->timestamp('ditutup_at')->nullable();
            $table->timestamps();
        });

        Schema::create('folio_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('folio')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('tipe', ['kamar','layanan','diskon','lainnya'])->default('layanan');
            $table->foreignId('layanan_id')->nullable()->constrained('layanan')->nullOnDelete();
            $table->string('deskripsi');
            $table->unsignedSmallInteger('qty')->default(1);
            $table->decimal('harga_satuan', 14, 2);
            $table->decimal('jumlah', 14, 2);           // qty x harga_satuan (negatif untuk diskon)
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['folio_id','tanggal','tipe'], 'idx_folio_item');
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('folio')->cascadeOnDelete();
            $table->string('nomor_pembayaran', 25)->unique();
            $table->enum('jenis', ['deposit','pembayaran','refund'])->default('pembayaran');
            $table->enum('metode', ['tunai','kartu_debit','kartu_kredit','transfer','qris'])->default('tunai');
            $table->decimal('jumlah', 14, 2);
            $table->string('referensi')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('night_audit', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->foreignId('dijalankan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('jumlah_in_house')->default(0);
            $table->unsignedSmallInteger('jumlah_diposting')->default(0);
            $table->decimal('total_pendapatan_kamar', 14, 2)->default(0);
            $table->decimal('okupansi', 5, 2)->default(0);
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('night_audit');
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('folio_item');
        Schema::dropIfExists('folio');
    }
};
