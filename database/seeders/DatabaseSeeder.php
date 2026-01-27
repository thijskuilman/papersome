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
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@papersome.com',
            'password' => bcrypt('password'),
        ]);

        $nos = Source::create([
            'name' => 'NOS Algemeen',
            'url' => 'https://feeds.nos.nl/nosnieuwsalgemeen',
            'type' => 'rss',
            'icon' => 'https://static.nos.nl/img/favicon/favicon-32x32.png',
            'html_query_filters' => [
                [
                    'selector' => 'all',
                    'query' => 'div[data-sentry-component="HeaderImage"]',
                ],
                [
                    'selector' => 'first',
                    'query' => 'div[data-sentry-component="RegionMeta"]',
                ],
                [
                    'selector' => 'first',
                    'query' => 'div[data-sentry-element="ItemContainer"]',
                ],
            ],
        ]);

        Source::create([
            'name' => 'Tweakers - Reviews',
            'url' => 'https://tweakers.net/feeds/reviews.xml',
            'type' => 'rss',
            'icon' => 'https://tweakers.net/android-touch-icon-192x192.png',
        ]);

        Source::create([
            'name' => 'NRC',
            'url' => 'https://nrc.nl/rss/',
            'type' => 'rss',
            'prefix_parse_url' => 'http://192.168.1.137:5000/',
            'icon' => 'https://assets.nrc.nl/static/front/icons/favicon.ico',
        ]);

        Source::create([
            'name' => 'Volkskrant',
            'url' => 'https://www.volkskrant.nl/nieuws-achtergrond/rss.xml',
            'type' => 'rss',
            'prefix_parse_url' => 'http://192.168.1.137:5000/',
            'icon' => 'https://www.volkskrant.nl/favicon.ico',
        ]);

        $collection = Collection::create([
            'name' => 'Daily News',
            'booklore_retention_hours' => 8,
            'enabled' => true,
            'schedule' => [
                [
                    "repeat_type" => ScheduleRepeatType::Specific->value,
                    "scheduled_days" => [ScheduledDay::Mon->value, ScheduledDay::Thu->value],
                    "time" => "07:00"
                ],
                [
                    "repeat_type" => ScheduleRepeatType::Daily->value,
                    "time" => "12:00"
                ],
                [
                    "repeat_type" => ScheduleRepeatType::Daily->value,
                    "time" => "21:00"
                ]
            ]
        ]);

        $collection->sources()->attach($nos);

        $bookloreUsername = config('booklore.username');
        $booklorePassword = config('booklore.password');
        $bookloreUrl = config('booklore.url');
        $bookloreLibraryId = config('booklore.library_id');

        if ($bookloreUsername && $booklorePassword && $bookloreUrl && $bookloreLibraryId) {
            $settings = app(ApplicationSettings::class);
            try {
                app(BookloreApiService::class)->login(
                    username: $bookloreUsername,
                    password: $booklorePassword,
                    url: $bookloreUrl,
                );
                $settings->booklore_library_id = config('booklore.library_id');
                $settings->save();
            } catch (\Exception) {
            }
        }

    }
}
