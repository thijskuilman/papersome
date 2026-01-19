<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('publications:generate')
    ->cron('0 7,12,19 * * *')
    ->runInBackground();

Schedule::command('booklore:process-deletion-requests')
    ->twiceDaily()
    ->runInBackground();

Schedule::command('publications:prune-retention')
    ->hourly()
    ->runInBackground();
