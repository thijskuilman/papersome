<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('publications:generate')
    ->twiceDaily(7, 22)
    ->runInBackground();
