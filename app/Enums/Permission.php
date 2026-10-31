<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @extends Enum<string>
 */
final class Permission extends Enum
{
    public const string PURCHASE_REQUEST_CREATE = 'purchase-request.create';

    public const string PURCHASE_REQUEST_APPROVE = 'purchase-request.approve';

    public const string PURCHASE_ORDER_MANAGE = 'purchase-order.manage';

    public const string GOODS_RECEIPT_CREATE = 'goods-receipt.create';

    public const string MASTER_DATA_MANAGE = 'master-data.manage';
}
