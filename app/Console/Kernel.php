<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // // Send low stock notification emails twice a day (9 AM and 3 PM)
        // $schedule->command('inventory:notify-low-stock')->twiceDaily(9, 15);
        // $schedule->command('inventory:check-low-stock')->everyFiveMinutes();
        
        // // Generate monthly inventory reports on the 1st of each month at 2 AM (run synchronously for cron)
        // $schedule->command('inventory:generate-monthly-report --sync')->monthlyOn(1, '02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
