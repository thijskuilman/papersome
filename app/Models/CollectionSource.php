<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CollectionSource extends Pivot
{
    /**
     * The table associated with the model.
     */
    protected $table = 'collection_source';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
