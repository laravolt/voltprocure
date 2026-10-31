<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Ordered = 'ordered';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Menunggu persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Ordered => 'Sudah dipesan',
        };
    }

    /**
     * Visual tone for the status badge; the label always travels with it.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Submitted => 'warning',
            self::Approved => 'accent',
            self::Rejected => 'danger',
            self::Ordered => 'success',
        };
    }
}
