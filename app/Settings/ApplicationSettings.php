<?php

namespace App\Settings;

use Carbon\Carbon;
use Spatie\LaravelSettings\Settings;

class ApplicationSettings extends Settings
{
    public ?string $booklore_url = null;

    public ?string $booklore_username = null;

    public ?string $booklore_library_id = null;

    public ?string $booklore_access_token = null;

    public ?string $booklore_refresh_token = null;

    public ?Carbon $booklore_access_token_expires_at = null;

    public int $booklore_retention_hours = 8;

    public static function group(): string
    {
        return 'application';
    }

    public static function encrypted(): array
    {
        return [
            'booklore_access_token',
            'booklore_refresh_token',
        ];
    }
}
