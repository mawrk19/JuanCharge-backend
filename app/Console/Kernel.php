<?php

namespace App\Console;

use App\Jobs\DetectMissedCollectionsJob;
use App\Jobs\RefreshLeaderboardSeasonsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Auto-complete expired charging sessions every minute
        $schedule->command('charging:complete-expired')->everyMinute();

        // Daily missed collection detection at 08:05 local timezone.
        $schedule->job(new DetectMissedCollectionsJob)
            ->dailyAt('08:05')
            ->timezone(config('app.timezone', 'UTC'));

        // Refresh leaderboard seasons and rotate windows when due.
        $schedule->job(new RefreshLeaderboardSeasonsJob)
            ->hourly()
            ->timezone(config('app.timezone', 'UTC'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
