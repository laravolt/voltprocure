<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Penerimaan',
            self::Issue => 'Pemakaian',
            self::Adjustment => 'Penyesuaian',
        };
    }
}
