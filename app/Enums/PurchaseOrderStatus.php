<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Issued => 'Terkirim ke pemasok',
            self::PartiallyReceived => 'Diterima sebagian',
            self::Received => 'Diterima lengkap',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Issued, self::PartiallyReceived], true);
    }
}
