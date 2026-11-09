<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin','frontdesk','housekeeping','finance'])->default('frontdesk')->after('email');
            $table->boolean('aktif')->default(true)->after('role');
            $table->timestamp('last_login')->nullable()->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role','aktif','last_login']);
        });
    }
};
