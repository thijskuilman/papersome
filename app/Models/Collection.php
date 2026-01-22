<?php

namespace App\Models;

use App\Enums\CoverTemplate;
use App\Enums\DeliveryChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'delivery_channel' => DeliveryChannel::class,
            'cover_template' => CoverTemplate::class,
            'schedule' => 'array',
            'enabled' => 'boolean',
        ];
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class, 'collection_source')
            ->withTimestamps();
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }
}
