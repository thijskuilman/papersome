<?php

namespace Database\Seeders;

use App\Enums\ScheduledDay;
use App\Enums\ScheduleRepeatType;
use App\Models\Collection;
use App\Models\Source;
use App\Models\User;
use App\Services\BookloreApiService;
use App\Settings\ApplicationSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //
    }
}
