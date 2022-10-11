<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class NewCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new
                            {directory : The directory to install WordPress into (required)}
                            {--core : Install core WordPress (optional)}
                            {--bedrock : Install Roots Bedrock (optional)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new WordPress powered website';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Do the thing.
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
