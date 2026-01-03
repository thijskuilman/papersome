<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Source;
use App\Models\User;
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
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@newspaparr.com',
            'password' => bcrypt('password'),
        ]);

        Source::create([
            'name' => 'NOS Algemeen',
            'url' => 'https://feeds.nos.nl/nosnieuwsalgemeen',
            'type' => 'rss',
        ]);

        Source::create([
            'name' => 'Tweakers - Reviews',
            'url' => 'https://tweakers.net/feeds/reviews.xml',
            'type' => 'rss',
        ]);

        Source::create([
            'name' => 'Leeuwarder Courant',
            'url' => 'https://lc.nl/api/feed/rss',
            'type' => 'rss',
        ]);

        Collection::create([
           'name' => 'Daily News',
           'delivery_channel' => 'booklore',
           'enabled' => true,
        ]);

    }
}
