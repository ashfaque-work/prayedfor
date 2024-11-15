<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\RefreshTokensCommand::class,
        // Commands\ReplyCommand::class,
    ];
    
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:refresh-tokens-command')->everySixHours()->runInBackground();

        // $schedule->call('App\Http\Controllers\UserController@fetchContacts')->everyMinute();
        // $schedule->call('App\Http\Controllers\MessageForwardController@saveUserByRole')->everyMinute();
        
        // $schedule->command('app:reply-command')->everyMinute()->runInBackground();
        // $schedule->call('App\Http\Controllers\MessageForwardController@countFlagged')->everyMinute();
        
        // $schedule->command('app:message-forwarding')->everyMinute()->runInBackground();
        // $schedule->command('app:prayer-count-command')->everyMinute()->runInBackground();
     
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
