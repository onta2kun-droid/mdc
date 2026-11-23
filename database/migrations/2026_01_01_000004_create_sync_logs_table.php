<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel sync_logs: jejak audit setiap sinkronisasi iCal (US-7).
     * TIDAK PERNAH menyimpan URL iCal utuh (hanya fingerprint) — NFR-01.
     */
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);            // ok | error
            $table->unsignedInteger('deadlines_count')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
