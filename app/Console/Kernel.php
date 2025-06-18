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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        // if(config('app.device') == "HIK")
        // {
        //     $schedule->command('command:get_attendance_hk')->everyMinute();
        //     // $schedule->command('command:get_attendance')->everyMinute();
        //     $schedule->command('command:send_attendance')->everyMinute();
        // }
        // else{
        //     $schedule->command('command:get_attendance')->everyMinute();
        // }
        // $schedule->command('command:send_attendance')->everyMinute();
        $schedule->command('command:whi_carmona_save_attendance')->everyMinute();
        $schedule->command('command:pbi_store_attendance')->everyMinute();
        // $schedule->command('command:pri_store_attendance')->everyMinute();
        $schedule->command('command:spai_store_attendance')->everyMinute();
        $schedule->command('command:wcc_store_attendance')->everyMinute();
        $schedule->command('command:wfa_store_attendance')->everyMinute();
        $schedule->command('command:wgc_store_attendance')->everyMinute();
        $schedule->command('command:woi_store_attendance')->everyMinute();
        $schedule->command('command:wtcc_store_attendance')->everyMinute();

        // $schedule->command('command:fmtcc_mrdc_store_attendance')->everyMinute();
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
