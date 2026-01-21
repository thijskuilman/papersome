<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('publications:generate')
    ->cron('0 7,12,19 * * *')
    ->runInBackground();

Schedule::command('booklore:process-deletion-requests')
    ->at('02:30')
    ->runInBackground();

Schedule::command('publications:prune-retention')
    ->hourly()
    ->runInBackground();
