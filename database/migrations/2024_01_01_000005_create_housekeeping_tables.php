<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('housekeeping_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamar_id')->constrained('kamar')->cascadeOnDelete();
            $table->foreignId('ditugaskan_ke')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipe', ['checkout_clean','stayover','inspeksi'])->default('checkout_clean');
            $table->enum('status', ['menunggu','dikerjakan','selesai'])->default('menunggu');
            $table->date('tanggal');
            $table->timestamp('mulai_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->index(['tanggal','status'], 'idx_hk_tanggal_status');
        });

        Schema::create('status_kamar_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamar_id')->constrained('kamar')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dari', 20)->nullable();
            $table->string('ke', 20);
            $table->string('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('laporan_kerusakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamar_id')->constrained('kamar')->cascadeOnDelete();
            $table->foreignId('dilaporkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->enum('prioritas', ['rendah','sedang','tinggi'])->default('sedang');
            $table->enum('status', ['dilaporkan','diproses','selesai'])->default('dilaporkan');
            $table->boolean('kamar_tidak_bisa_dipakai')->default(false);
            $table->timestamp('diselesaikan_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_kerusakan');
        Schema::dropIfExists('status_kamar_log');
        Schema::dropIfExists('housekeeping_task');
    }
};
