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
}
