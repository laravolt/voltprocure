<?php

declare(strict_types=1);

use App\Enums\Permission;

return [
    'Pengadaan' => [
        'order' => 1,
        'menu' => [
            'Ringkasan' => [
                'route' => 'home',
                'active' => 'home',
                'icon' => 'chart-pie',
            ],
            'Permintaan barang' => [
                'route' => 'purchase-requests.index',
                'active' => 'purchase-requests*',
                'icon' => 'clipboard-list',
                'permissions' => [Permission::PURCHASE_REQUEST_CREATE, Permission::PURCHASE_REQUEST_APPROVE, Permission::PURCHASE_ORDER_MANAGE],
            ],
            'Pesanan pembelian' => [
                'route' => 'purchase-orders.index',
                'active' => 'purchase-orders*',
                'icon' => 'file-invoice',
                'permissions' => [Permission::PURCHASE_ORDER_MANAGE, Permission::GOODS_RECEIPT_CREATE],
            ],
        ],
    ],
    'Gudang' => [
        'order' => 2,
        'menu' => [
            'Stok barang' => [
                'route' => 'items.index',
                'active' => 'items*',
                'icon' => 'boxes',
            ],
            'Pemasok' => [
                'route' => 'suppliers.index',
                'active' => 'suppliers*',
                'icon' => 'truck',
                'permissions' => [Permission::PURCHASE_ORDER_MANAGE],
            ],
        ],
    ],
];
