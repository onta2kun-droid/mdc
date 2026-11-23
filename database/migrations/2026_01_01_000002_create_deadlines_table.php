<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel deadlines: hasil parsing file .ics Moodle (tugas/kuis dan tenggat).
     * Constraint unik (user_id, uid) mencegah duplikasi saat sinkronisasi
     * berulang (upsert) — sesuai FR-03 dan risiko no. 4 pada LKM 9.
     */
    public function up(): void
    {
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moodle_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('uid', 255);              // UID dari VEVENT (deduplikasi)
            $table->string('course')->index();       // nama mata kuliah
            $table->string('title');                 // nama tugas/kuis
            $table->string('type', 20)->default('tugas'); // tugas | kuis | lainnya
            $table->dateTime('starts_at')->index();  // waktu tenggat
            $table->text('url')->nullable();         // tautan ke aktivitas Moodle
            $table->text('description')->nullable();
            $table->timestamp('completed_at')->nullable()->index(); // penandaan selesai (US-5)
            $table->timestamps();

            $table->unique(['user_id', 'uid']);
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
