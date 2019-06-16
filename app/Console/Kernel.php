<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        Commands\SeedDB::class,
        Commands\MigrationOne::class,
        Commands\MigrationZero::class,
        Commands\MigrationTwo::class,
        Commands\MigrationTeachers::class,
        Commands\MigrationStudents::class,
        Commands\MigrationSSessions::class,
        Commands\MigrationTSessions::class,
        Commands\MigrationTables::class,
        Commands\MigrationExperiment::class,
        Commands\MigrationCount::class,
        Commands\MigrationParingSessions::class,
        Commands\MigrationExp::class,
        Commands\Duplicates::class,
        Commands\MigrationIncome::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        // $schedule->command('inspire')
        //          ->hourly();
    }

}
