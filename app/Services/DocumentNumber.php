<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * Sequential document numbers per prefix and year, e.g. PR-2026-0007.
 */
final class DocumentNumber
{
    /**
     * @param  class-string<Model>  $model
     */
    public function next(string $model, string $prefix): string
    {
        $base = sprintf('%s-%s-', $prefix, Date::now()->format('Y'));

        /** @var string|null $last */
        $last = $model::query()
            ->where('number', 'like', $base.'%')
            ->lockForUpdate()
            ->max('number');

        $sequence = $last === null ? 1 : ((int) mb_substr($last, mb_strlen($base))) + 1;

        return $base.mb_str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
