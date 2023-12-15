<?php

namespace Indeev\LaravelScheduleCalendar;

use Illuminate\Support\ServiceProvider;
use Indeev\LaravelScheduleCalendar\Console\Commands\ScheduleCalendarCommand;

class LaravelScheduleCalendarServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap application service
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ScheduleCalendarCommand::class,
            ]);
        }
    }

    /**
     * Register application service
     */
    public function register()
    {
        
    }
}
