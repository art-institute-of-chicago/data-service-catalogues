<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Use this to import third-party Artisan commands.
     *
     * @var array
     */
    protected $commands = [
        \Aic\Hub\Foundation\Commands\DatabaseReset::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->command('import:pubs --redownload --quiet')
            ->dailyAt('23:' .(config('app.env') == 'production' ? '00' : '15'))
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/import-last-run.log'));

        $schedule->command('match:artworks --quiet')
            ->weeklyOn(6, '03:' .(config('app.env') == 'production' ? '00' : '15'))
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/match-last-run.log'));

    }

    /**
     * Use this to auto-load directories with commands.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
