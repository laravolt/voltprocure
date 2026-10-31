<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['code', 'name', 'manager_id', 'is_active'];

    /**
     * @var array<string, string>
     */
    protected $casts = ['is_active' => 'boolean'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return HasMany<PurchaseRequest, $this>
     */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }
}
