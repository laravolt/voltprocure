<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @extends Enum<string>
 */
final class Permission extends Enum
{
    const string PURCHASE_REQUEST_CREATE = 'purchase-request.create';

    const string PURCHASE_REQUEST_APPROVE = 'purchase-request.approve';

    const string PURCHASE_ORDER_MANAGE = 'purchase-order.manage';

    const string GOODS_RECEIPT_CREATE = 'goods-receipt.create';

    const string MASTER_DATA_MANAGE = 'master-data.manage';
}
