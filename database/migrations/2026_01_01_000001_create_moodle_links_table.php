<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel moodle_links: menyimpan URL ekspor kalender (iCal) Moodle milik
     * setiap mahasiswa. URL disimpan TERENKRIPSI (Laravel "encrypted" cast)
     * karena di dalamnya terdapat authtoken (NFR-01, risiko no. 2 pada LKM 9).
     * Kolom url_fingerprint menyimpan hash host+path agar aplikasi tetap bisa
     * menampilkan "sudah terhubung ke mana" tanpa membocorkan token.
     */
    public function up(): void
    {
        Schema::create('moodle_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('url_encrypted');
            $table->string('url_fingerprint', 64)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_status', 20)->nullable(); // ok | error
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moodle_links');
    }
};
