<?php

namespace App\Console;

use App\Console\Commands\Schedule as CommandsSchedule;
use App\Console\Commands\ScheduleReoccurring;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('app:schedule')->everyMinute();
        $schedule->command('app:schedule-reoccurring')->everyMinute();
    }

    protected $commands = [
        CommandsSchedule::class,
        ScheduleReoccurring::class,
    ];
    
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
