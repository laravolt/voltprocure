<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Procurement\CreatePurchaseOrder;
use App\Actions\Procurement\CreatePurchaseRequest;
use App\Actions\Procurement\DecidePurchaseRequest;
use App\Actions\Procurement\ReceiveGoods;
use App\Actions\Procurement\SubmitPurchaseRequest;
use App\Enums\Permission;
use App\Models\Department;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a believable two-month history for the VoltProcure demo. Every
 * document goes through the real actions, so statuses, totals and stock
 * always agree with each other.
 */
final class ProcurementDemoSeeder extends Seeder
{
    /** @var array<string, User> */
    private array $users = [];

    public function run(): void
    {
        $now = Date::now();
        mt_srand(2026);

        $this->roles();
        $departments = $this->departments();
        $items = $this->items();
        $suppliers = $this->suppliers();

        $scenarios = [
            // days ago, department, requester, purpose, [sku => qty], outcome
            [58, 'TI', 'dewi', 'Toner printer lantai 3 habis', ['ITM-0101' => 8, 'ITM-0102' => 4], 'received'],
            [55, 'OPS', 'yoga', 'Perlengkapan rapat kuartal IV', ['ITM-0201' => 20, 'ITM-0203' => 10], 'received'],
            [51, 'KEU', 'maya', 'Kertas dan ordner arsip pajak', ['ITM-0201' => 30, 'ITM-0204' => 25], 'received'],
            [47, 'TI', 'dewi', 'Keyboard dan mouse karyawan baru', ['ITM-0103' => 6, 'ITM-0104' => 6], 'received'],
            [44, 'GDG', 'fajar', 'Label rak dan plastik wrap', ['ITM-0301' => 40, 'ITM-0302' => 12], 'received'],
            [40, 'OPS', 'yoga', 'Kursi ruang tunggu tamu', ['ITM-0401' => 6], 'rejected'],
            [37, 'TI', 'dewi', 'Switch dan kabel ruang server', ['ITM-0105' => 2, 'ITM-0106' => 30], 'received'],
            [33, 'KEU', 'maya', 'Kalkulator dan stempel', ['ITM-0205' => 6, 'ITM-0206' => 4], 'received'],
            [29, 'GDG', 'fajar', 'Sarung tangan dan masker gudang', ['ITM-0303' => 50, 'ITM-0304' => 100], 'partial'],
            [26, 'TI', 'dewi', 'SSD laptop tim support', ['ITM-0107' => 5], 'received'],
            [22, 'OPS', 'yoga', 'Air mineral dan kopi pantry', ['ITM-0501' => 30, 'ITM-0502' => 12], 'received'],
            [19, 'KEU', 'maya', 'Map arsip dan kertas continuous form', ['ITM-0204' => 40, 'ITM-0207' => 10], 'partial'],
            [16, 'TI', 'dewi', 'Monitor 24 inci untuk tim desain', ['ITM-0108' => 3], 'ordered'],
            [13, 'GDG', 'fajar', 'Palet plastik tambahan', ['ITM-0305' => 15], 'ordered'],
            [11, 'OPS', 'yoga', 'Spidol dan kertas flipchart', ['ITM-0208' => 24, 'ITM-0209' => 6], 'partial'],
            [8, 'KEU', 'maya', 'Toner printer keuangan', ['ITM-0101' => 4], 'approved'],
            [6, 'TI', 'dewi', 'Lisensi antivirus perpanjangan', ['ITM-0109' => 25], 'approved'],
            [5, 'GDG', 'fajar', 'Lakban dan cutter', ['ITM-0306' => 48, 'ITM-0307' => 12], 'submitted'],
            [3, 'OPS', 'yoga', 'Tisu dan sabun toilet', ['ITM-0503' => 40, 'ITM-0504' => 20], 'submitted'],
            [2, 'TI', 'dewi', 'UPS cadangan ruang server', ['ITM-0110' => 2], 'submitted'],
            [1, 'KEU', 'maya', 'Kertas A4 bulan November', ['ITM-0201' => 25], 'submitted'],
            [0, 'GDG', 'fajar', 'Baterai pemindai barcode', ['ITM-0308' => 30], 'draft'],
        ];

        foreach ($scenarios as [$daysAgo, $code, $requester, $purpose, $lines, $outcome]) {
            $this->play($now, $daysAgo, $departments[$code], $this->users[$requester], $purpose, $lines, $outcome, $items, $suppliers);
        }

        Date::setTestNow();
    }

    private function roles(): void
    {
        $roleModel = config('laravolt.epicentrum.models.role');

        $definitions = [
            'Pemohon' => [Permission::PURCHASE_REQUEST_CREATE],
            'Manajer departemen' => [Permission::PURCHASE_REQUEST_CREATE, Permission::PURCHASE_REQUEST_APPROVE],
            'Pengadaan' => [Permission::PURCHASE_ORDER_MANAGE, Permission::MASTER_DATA_MANAGE],
            'Gudang' => [Permission::GOODS_RECEIPT_CREATE],
        ];

        $roles = [];
        foreach ($definitions as $name => $permissions) {
            $roles[$name] = $roleModel::query()->firstOrCreate(['name' => $name]);
            $roles[$name]->syncPermission($permissions);
        }

        $people = [
            'dewi' => ['Dewi Lestari', 'Pemohon'],
            'yoga' => ['Yoga Prasetyo', 'Pemohon'],
            'maya' => ['Maya Sari', 'Pemohon'],
            'fajar' => ['Fajar Nugroho', 'Pemohon'],
            'bagus' => ['Bagus Santoso', 'Manajer departemen'],
            'wulan' => ['Wulan Anggraini', 'Manajer departemen'],
            'hendra' => ['Hendra Wijaya', 'Manajer departemen'],
            'ratna' => ['Ratna Kusuma', 'Manajer departemen'],
            'andi' => ['Andi Pratama', 'Pengadaan'],
            'siti' => ['Siti Rahmawati', 'Gudang'],
        ];

        foreach ($people as $key => [$name, $role]) {
            $user = User::query()->firstOrCreate(
                ['email' => $key.'@voltprocure.test'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'status' => 'ACTIVE',
                    'timezone' => 'Asia/Jakarta',
                    'email_verified_at' => Date::now(),
                ],
            );
            $user->assignRole($roles[$role]);
            $this->users[$key] = $user;
        }
    }

    /**
     * @return array<string, Department>
     */
    private function departments(): array
    {
        $departments = [];
        foreach ([
            'OPS' => ['Operasional', 'wulan', 'yoga'],
            'TI' => ['Teknologi Informasi', 'bagus', 'dewi'],
            'KEU' => ['Keuangan', 'hendra', 'maya'],
            'GDG' => ['Gudang dan Logistik', 'ratna', 'fajar'],
        ] as $code => [$name, $manager, $staff]) {
            $departments[$code] = Department::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'manager_id' => $this->users[$manager]->getKey()],
            );
            foreach ([$manager, $staff] as $member) {
                $this->users[$member]->update(['department_id' => $departments[$code]->getKey()]);
            }
        }

        return $departments;
    }

    /**
     * @return array<string, Item>
     */
    private function items(): array
    {
        $catalog = [
            ['ITM-0101', 'Toner HP 26A', 'Perangkat TI', 'pcs', 6, 845_000, 3],
            ['ITM-0102', 'Drum unit Brother DR-2455', 'Perangkat TI', 'pcs', 2, 1_120_000, 1],
            ['ITM-0103', 'Keyboard Logitech K120', 'Perangkat TI', 'pcs', 5, 135_000, 4],
            ['ITM-0104', 'Mouse Logitech M185', 'Perangkat TI', 'pcs', 5, 165_000, 2],
            ['ITM-0105', 'Switch TP-Link 24 port', 'Jaringan', 'unit', 1, 2_350_000, 0],
            ['ITM-0106', 'Kabel UTP Cat6 3 m', 'Jaringan', 'pcs', 20, 38_000, 12],
            ['ITM-0107', 'SSD Samsung 1 TB', 'Perangkat TI', 'pcs', 2, 1_275_000, 1],
            ['ITM-0108', 'Monitor Dell 24 inci', 'Perangkat TI', 'unit', 1, 2_480_000, 0],
            ['ITM-0109', 'Lisensi antivirus 1 tahun', 'Perangkat lunak', 'lisensi', 0, 210_000, 0],
            ['ITM-0110', 'UPS APC 1200 VA', 'Perangkat TI', 'unit', 1, 1_890_000, 1],
            ['ITM-0201', 'Kertas A4 80 g', 'Alat tulis kantor', 'rim', 40, 54_000, 18],
            ['ITM-0203', 'Buku agenda rapat', 'Alat tulis kantor', 'pcs', 10, 32_000, 6],
            ['ITM-0204', 'Ordner Bantex folio', 'Alat tulis kantor', 'pcs', 30, 27_500, 14],
            ['ITM-0205', 'Kalkulator Casio 12 digit', 'Alat tulis kantor', 'pcs', 3, 145_000, 2],
            ['ITM-0206', 'Stempel otomatis', 'Alat tulis kantor', 'pcs', 2, 185_000, 1],
            ['ITM-0207', 'Kertas continuous form 3 ply', 'Alat tulis kantor', 'box', 5, 410_000, 2],
            ['ITM-0208', 'Spidol whiteboard', 'Alat tulis kantor', 'pcs', 24, 9_500, 10],
            ['ITM-0209', 'Kertas flipchart', 'Alat tulis kantor', 'pak', 4, 68_000, 1],
            ['ITM-0301', 'Label rak 50 x 100 mm', 'Perlengkapan gudang', 'roll', 20, 46_000, 9],
            ['ITM-0302', 'Plastik wrap 50 cm', 'Perlengkapan gudang', 'roll', 10, 95_000, 4],
            ['ITM-0303', 'Sarung tangan kerja', 'Perlengkapan gudang', 'pasang', 40, 18_500, 22],
            ['ITM-0304', 'Masker medis', 'Perlengkapan gudang', 'box', 50, 32_000, 31],
            ['ITM-0305', 'Palet plastik 120 x 100', 'Perlengkapan gudang', 'pcs', 10, 385_000, 6],
            ['ITM-0306', 'Lakban cokelat 48 mm', 'Perlengkapan gudang', 'roll', 48, 12_500, 20],
            ['ITM-0307', 'Cutter besar', 'Perlengkapan gudang', 'pcs', 10, 21_000, 7],
            ['ITM-0308', 'Baterai AA isi ulang', 'Perlengkapan gudang', 'pcs', 24, 27_000, 8],
            ['ITM-0401', 'Kursi tunggu 3 dudukan', 'Mebel', 'unit', 0, 1_650_000, 0],
            ['ITM-0501', 'Air mineral galon', 'Pantry', 'galon', 20, 22_000, 9],
            ['ITM-0502', 'Kopi bubuk 500 g', 'Pantry', 'pak', 6, 78_000, 3],
            ['ITM-0503', 'Tisu gulung', 'Pantry', 'roll', 40, 6_500, 25],
            ['ITM-0504', 'Sabun cuci tangan 500 ml', 'Pantry', 'botol', 12, 24_000, 5],
        ];

        $items = [];
        foreach ($catalog as [$sku, $name, $category, $unit, $reorderPoint, $cost, $openingStock]) {
            $items[$sku] = Item::query()->firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'reorder_point' => $reorderPoint,
                'standard_cost' => $cost,
                'stock_on_hand' => $openingStock,
            ]);
        }

        return $items;
    }

    /**
     * @return list<Supplier>
     */
    private function suppliers(): array
    {
        $suppliers = [];
        foreach ([
            ['SUP-0001', 'PT Sinar Data Komputindo', 'Rudi Hartono', 'Jakarta Pusat', '30 hari'],
            ['SUP-0002', 'CV Mitra Kantor Mandiri', 'Lina Marlina', 'Bandung', '14 hari'],
            ['SUP-0003', 'PT Gudang Sarana Utama', 'Teguh Wibowo', 'Bekasi', '30 hari'],
            ['SUP-0004', 'Toko Pantry Segar', 'Nur Aini', 'Bandung', 'Tunai'],
            ['SUP-0005', 'PT Jaringan Nusantara Teknik', 'Agus Salim', 'Jakarta Selatan', '45 hari'],
        ] as [$code, $name, $contact, $city, $terms]) {
            $suppliers[] = Supplier::query()->firstOrCreate(['code' => $code], [
                'name' => $name,
                'contact_name' => $contact,
                'email' => mb_strtolower(str_replace(' ', '.', $contact)).'@example.com',
                'city' => $city,
                'payment_terms' => $terms,
            ]);
        }

        return $suppliers;
    }

    /**
     * @param  array<string, int>  $lines
     * @param  array<string, Item>  $items
     * @param  list<Supplier>  $suppliers
     */
    private function play(CarbonImmutable $now, int $daysAgo, Department $department, User $requester, string $purpose, array $lines, string $outcome, array $items, array $suppliers): void
    {
        $at = fn (int $days, int $hour): CarbonImmutable => $now->copy()->subDays($days)->setTime($hour, mt_rand(0, 59));

        Date::setTestNow($at($daysAgo, 9));
        $request = resolve(CreatePurchaseRequest::class)->handle($requester, [
            'purpose' => $purpose,
            'needed_by' => $now->copy()->subDays($daysAgo)->addDays(14)->toDateString(),
            'lines' => array_map(
                fn (string $sku, int $quantity): array => ['item_id' => $items[$sku]->getKey(), 'quantity' => $quantity],
                array_keys($lines),
                $lines,
            ),
        ]);

        if ($outcome === 'draft') {
            return;
        }

        resolve(SubmitPurchaseRequest::class)->handle($request, $requester);
        if ($outcome === 'submitted') {
            return;
        }

        Date::setTestNow($at(max(0, $daysAgo - 1), 14));
        $manager = $department->manager;
        $decide = resolve(DecidePurchaseRequest::class);
        if ($outcome === 'rejected') {
            $decide->reject($request->fresh(), $manager, 'Belum masuk anggaran kuartal ini');

            return;
        }
        $decide->approve($request->fresh(), $manager);
        if ($outcome === 'approved') {
            return;
        }

        Date::setTestNow($at(max(0, $daysAgo - 2), 10));
        $supplier = $suppliers[mt_rand(0, count($suppliers) - 1)];
        $order = resolve(CreatePurchaseOrder::class)->handle(
            $request->fresh(),
            $supplier,
            $this->users['andi'],
            $now->copy()->subDays($daysAgo)->addDays(10)->toDateString(),
        );
        if ($outcome === 'ordered') {
            return;
        }

        Date::setTestNow($at(max(0, $daysAgo - 6), 11));
        $quantities = $order->lines->mapWithKeys(fn ($line): array => [
            $line->getKey() => $outcome === 'partial' ? (int) ceil($line->quantity_ordered / 2) : $line->quantity_ordered,
        ])->all();
        resolve(ReceiveGoods::class)->handle($order, $this->users['siti'], $quantities);
    }
}
