<?php

namespace App\Services;

use App\Enums\ActivityLogChannel;
use App\Enums\ActivityLogType;
use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LogService
{
    public function info(string $message, ActivityLogChannel $channel, ?Command $command = null, ?array $data = []): void {
        $this->log(
            type: ActivityLogType::Info,
            message: $message,
            channel: $channel,
            data: $data,
        );

        if($command) {
            $command->info($message);
        }
    }

    public function success(string $message, ActivityLogChannel $channel, ?Command $command = null, ?array $data = []): void {
        $this->log(
            type: ActivityLogType::Success,
            message: $message,
            channel: $channel,
            data: $data,
        );

        if($command) {
            $command->info($message);
        }
    }

    public function error(string $message, ActivityLogChannel $channel, ?Command $command = null, ?array $data = []): void {
        $this->log(
            type: ActivityLogType::Error,
            message: $message,
            channel: $channel,
            data: $data,
        );

        if($command) {
            $command->error($message);
        }

        Log::error($message, $data ?? []);
    }

    private function log(ActivityLogType $type, string $message, ActivityLogChannel $channel, array $data = []): void
    {
        ActivityLog::create([
            'type' => $type,
            'channel' => $channel,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
