<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('publications:scan-and-dispatch')
    ->everyMinute()
    ->runInBackground();

Schedule::command('booklore:process-deletion-requests')
    ->at('02:30')
    ->runInBackground();

Schedule::command('publications:prune-retention')
    ->hourly()
    ->runInBackground();
