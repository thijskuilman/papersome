<?php

namespace App\Models;

use App\Enums\CoverTemplate;
use App\Enums\ScheduleRepeatType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Collection extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'cover_template' => CoverTemplate::class,
            'schedule' => 'array',
            'enabled' => 'boolean',
        ];
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class, 'collection_source')
            ->withTimestamps()
            ->withPivot('max_article_count', 'sort');
    }

    public function collectionSources(): HasMany
    {
        return $this->hasMany(CollectionSource::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    /**
     * Determine if this collection is due for generating new publications
     */
    public function isDueAt(Carbon $now): bool
    {
        $schedule = $this->schedule ?? [];

        if (! $schedule) {
            return false;
        }

        foreach ($schedule as $entry) {
            if ($entry['time'] !== $now->format('H:i')) {
                continue;
            }

            return match ($entry['repeat_type']) {
                ScheduleRepeatType::Daily->value => true,
                ScheduleRepeatType::Specific->value => in_array(
                    strtolower($now->format('D')),
                    array_map(strtolower(...), $entry['scheduled_days'] ?? []),
                    true
                ),
            };
        }

        return false;
    }
}
