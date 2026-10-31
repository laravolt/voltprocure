<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class StockMovement extends Model
{
    use HasUlids;

    protected $fillable = ['item_id', 'quantity', 'type', 'source_type', 'source_id', 'created_by', 'moved_at'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'type' => StockMovementType::class,
        'moved_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
