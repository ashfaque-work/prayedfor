<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\MessageForwardController;


class PrayerCountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prayer-count-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $msgForwardController = new MessageForwardController();
        $msgForwardController->prayerCountMsg();
    }
}
