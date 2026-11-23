<?php

return [

    /*
    |------------------------------------------------------------------
    | Konfigurasi Moodle Deadline Companion (MDC)
    |------------------------------------------------------------------
    | Nilai dapat diubah lewat file .env agar mudah disesuaikan di
    | server tanpa mengubah kode (NFR-05 Maintainability).
    */

    // Jeda minimum antar sinkronisasi per pengguna (menit) — rate limit.
    'min_sync_interval' => env('MDC_MIN_SYNC_INTERVAL', 5),

    // Interval sinkronisasi berkala oleh scheduler (menit) — FR-03.
    'sync_interval' => (int) env('MDC_SYNC_INTERVAL', 30),

    // Jendela pengingat, dalam jam sebelum tenggat — FR-05.
    'h1_hours' => (int) env('MDC_H1_HOURS', 24),
    'h3_hours' => (int) env('MDC_H3_HOURS', 3),

];
