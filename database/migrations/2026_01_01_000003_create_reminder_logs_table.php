<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel reminder_logs: bukti terkirimnya pengingat dan kunci idempotensi.
     * Constraint unik (deadline_id, channel, reminder_type) menjamin satu
     * pengingat H-1 / H-3 hanya dikirim satu kali per kanal meskipun scheduler
     * berjalan ganda (US-4, risiko no. 4 pada LKM 9).
     */
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20);           // email | telegram
            $table->string('reminder_type', 10);     // h1 | h3
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['deadline_id', 'channel', 'reminder_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
