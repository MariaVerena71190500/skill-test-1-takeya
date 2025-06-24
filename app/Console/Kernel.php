<?php

namespace App\Console;

use App\Console\Commands\PublishScheduledPosts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('posts:publish-scheduled')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected $commands = [
        PublishScheduledPosts::class,

    ];

    protected $middlewareGroups = [
        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
}
