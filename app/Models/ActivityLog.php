<?php

namespace App\Models;

use App\Enums\ActivityLogChannel;
use App\Enums\ActivityLogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityLogType::class,
            'channel' => ActivityLogChannel::class,
            'data' => 'json',
        ];
    }
}
