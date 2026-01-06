<?php

namespace App\Dto;

use App\Enums\BookloreConnectionStatus;

class BookloreConnection
{
    public function __construct(
        public BookloreConnectionStatus $status,
        public ?string $message = null,
    ) {}
}
