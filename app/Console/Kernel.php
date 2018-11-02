<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ImportPublications::class,
        Commands\ValidateSections::class,
        Commands\CountArtworks::class,
        Commands\MatchArtworks::class,
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
            ->appendOutputTo(storage_path('logs/import.log'))
            ->sendOutputTo(storage_path('logs/import-last-run.log'))
            ->emailOutputTo([env('LOG_EMAIL_1'), env('LOG_EMAIL_2')], true);

        $schedule->command('match:artworks --quiet')
            ->weeklyOn(6, '03:' .(config('app.env') == 'production' ? '00' : '15'))
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/match.log'))
            ->sendOutputTo(storage_path('logs/match-last-run.log'))
            ->emailOutputTo([env('LOG_EMAIL_1'), env('LOG_EMAIL_2')], true);

    }
}
