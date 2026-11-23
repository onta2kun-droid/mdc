<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Jadwal (scheduler) Moodle Deadline Companion
|--------------------------------------------------------------------------
| Dijalankan oleh: `php artisan schedule:run` (cron tiap menit di server)
| atau `php artisan schedule:work` (container Docker).
*/

// FR-03: sinkronisasi iCal Moodle berkala — tiap 30 menit.
Schedule::command('mdc:sync')->everyThirtyMinutes()->withoutOverlapping();

// FR-05: pengingat H-1 dan 3 jam sebelum tenggat — cek tiap 5 menit
// agar tidak ada jendela yang terlewat; idempoten oleh reminder_logs.
Schedule::command('mdc:send-reminders')->everyFiveMinutes()->withoutOverlapping();
