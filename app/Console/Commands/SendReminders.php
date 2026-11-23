<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'mdc:send-reminders';

    protected $description = 'Kirim pengingat H-1 dan 3 jam sebelum tenggat (idempoten)';

    public function handle(ReminderService $reminders): int
    {
        $result = $reminders->dispatchDue();
        $this->info("Pengingat terkirim: {$result['sent']}.");

        return self::SUCCESS;
    }
}
