<?php

namespace App\Settings;

use Carbon\Carbon;
use Spatie\LaravelSettings\Settings;

class ApplicationSettings extends Settings
{
    public string $timezone;

    public static function group(): string
    {
        return 'application';
    }
}
