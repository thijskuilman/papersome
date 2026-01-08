<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('publications:generate')
    ->dailyAt('05:00')
    ->runInBackground();
