<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Carbon\Carbon;

class ApplicationSettings extends Settings
{
    public ?string $booklore_url;

    public ?string $booklore_username;
    public ?string $booklore_password;

    public ?string $booklore_library_id;

    public ?string $booklore_access_token;
    public ?string $booklore_refresh_token;
    public ?Carbon $booklore_access_token_expires_at;

    public static function group(): string
    {
        return 'application';
    }

    public static function encrypted(): array
    {
        return [
            'booklore_password',
            'booklore_access_token',
            'booklore_refresh_token',
        ];
    }
}

