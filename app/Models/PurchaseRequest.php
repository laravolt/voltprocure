<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class PurchaseRequest extends Model
{
    /** @use HasFactory<PurchaseRequestFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['number', 'department_id', 'requester_id', 'purpose', 'needed_by', 'status', 'submitted_at', 'decided_by', 'decided_at', 'decision_note'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => PurchaseRequestStatus::class,
        'needed_by' => 'date',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return HasMany<PurchaseRequestLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class);
    }

    /**
     * @return HasOne<PurchaseOrder, $this>
     */
    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    public function estimatedTotal(): int
    {
        return (int) $this->lines->sum(
            fn (PurchaseRequestLine $line): int => ($line->approved_quantity ?? $line->quantity) * $line->estimated_unit_cost,
        );
    }
}
