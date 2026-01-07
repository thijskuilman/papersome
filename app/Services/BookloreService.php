<?php

namespace App\Services;

use App\Settings\ApplicationSettings;
use Exception;
use Illuminate\Support\Facades\Http;

class BookloreService
{
    public function __construct(private readonly ApplicationSettings $settings) {}

    /**
     * @throws Exception
     */
    public function syncToBooklore(): void
    {
        dd($this->getAccessToken());
    }
}
