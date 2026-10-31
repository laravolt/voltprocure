<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Queries\VisiblePurchaseRequests;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final readonly class HomeController
{
    private const int CHART_WEEKS = 8;

    public function __construct(private VisiblePurchaseRequests $visible) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $now = CarbonImmutable::instance(Date::now());

        return view('home', [
            'user' => $user,
            'now' => $now,
            'pending' => $this->pendingApprovals($user),
            'openOrders' => $this->openOrders(),
            'lowStock' => $this->lowStock(),
            'spend' => $this->monthlySpend($now),
            'weeks' => $this->weeklySpend($now),
            'work' => $this->workFor($user, $now),
        ]);
    }

    /**
     * @return array{count: int, value: int}
     */
    private function pendingApprovals(User $user): array
    {
        $requests = $this->visible->for($user)
            ->where('status', PurchaseRequestStatus::Submitted)
            ->with('lines')
            ->get();

        return [
            'count' => $requests->count(),
            'value' => (int) $requests->sum(fn (PurchaseRequest $request): int => $request->estimatedTotal()),
        ];
    }

    /**
     * @return array{count: int, value: int, overdue: int}
     */
    private function openOrders(): array
    {
        $orders = PurchaseOrder::query()
            ->whereIn('status', [PurchaseOrderStatus::Issued, PurchaseOrderStatus::PartiallyReceived])
            ->with('lines')
            ->get();

        return [
            'count' => $orders->count(),
            'value' => (int) $orders->sum(fn (PurchaseOrder $order): int => $order->outstandingValue()),
            'overdue' => $orders->filter(fn (PurchaseOrder $order): bool => $order->isOverdue())->count(),
        ];
    }

    /**
     * @return Collection<int, Item>
     */
    private function lowStock(): Collection
    {
        return Item::query()
            ->where('is_active', true)
            ->where('reorder_point', '>', 0)
            ->whereColumn('stock_on_hand', '<=', 'reorder_point')
            ->orderByRaw('stock_on_hand * 1.0 / reorder_point')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{current: int, previous: int}
     */
    private function monthlySpend(CarbonImmutable $now): array
    {
        $sum = fn (CarbonImmutable $from, CarbonImmutable $to): int => (int) PurchaseOrder::query()
            ->whereBetween('issued_at', [$from, $to])
            ->sum('total');

        $previousStart = $now->startOfMonth()->subMonth();

        return [
            'current' => $sum($now->startOfMonth(), $now),
            // Same number of days into last month, so the comparison is fair mid-month.
            'previous' => $sum($previousStart, $previousStart->addDays($now->day - 1)->endOfDay()->min($previousStart->endOfMonth())),
        ];
    }

    /**
     * Order value issued per week (Monday start), oldest first.
     *
     * @return list<array{start: CarbonImmutable, total: int, orders: int}>
     */
    private function weeklySpend(CarbonImmutable $now): array
    {
        $firstWeek = $now->startOfWeek()->subWeeks(self::CHART_WEEKS - 1);

        $orders = PurchaseOrder::query()
            ->where('issued_at', '>=', $firstWeek)
            ->get(['issued_at', 'total']);

        $weeks = [];
        for ($i = 0; $i < self::CHART_WEEKS; $i++) {
            $start = $firstWeek->addWeeks($i);
            $inWeek = $orders->filter(fn (PurchaseOrder $order): bool => $order->issued_at !== null
                && $order->issued_at->betweenIncluded($start, $start->endOfWeek()));

            $weeks[] = [
                'start' => $start,
                'total' => $inWeek->sum(fn (PurchaseOrder $order): int => $order->total),
                'orders' => $inWeek->count(),
            ];
        }

        return $weeks;
    }

    /**
     * Today's work for this person, most urgent first. Each permission adds
     * the documents that are waiting on that role.
     *
     * @return list<array{number: string, title: string, meta: string, action: string, url: string, urgent: bool}>
     */
    private function workFor(User $user, CarbonImmutable $now): array
    {
        $work = [];

        $toDecide = $this->visible->for($user)
            ->where('status', PurchaseRequestStatus::Submitted)
            ->with(['requester', 'department', 'lines'])
            ->oldest('submitted_at')
            ->get()
            ->filter(fn (PurchaseRequest $request): bool => $this->visible->canDecide($user, $request));

        foreach ($toDecide as $request) {
            $submittedAt = $request->submitted_at;
            $waitingDays = $submittedAt instanceof CarbonInterface ? (int) $submittedAt->diffInDays($now) : 0;
            $work[] = [
                'number' => $request->number,
                'title' => $request->purpose,
                'meta' => sprintf('%s, %s · menunggu %d hari', $this->requesterName($request), $request->department?->code, $waitingDays),
                'action' => 'Putuskan',
                'url' => route('purchase-requests.show', $request),
                'urgent' => $waitingDays >= 3,
            ];
        }

        if ($user->hasPermission(Permission::PURCHASE_ORDER_MANAGE) || $user->hasPermission('*')) {
            $toOrder = PurchaseRequest::query()
                ->where('status', PurchaseRequestStatus::Approved)
                ->with('department')
                ->oldest('decided_at')
                ->get();

            foreach ($toOrder as $request) {
                $work[] = [
                    'number' => $request->number,
                    'title' => $request->purpose,
                    'meta' => sprintf('Disetujui %s · %s', $this->shortDate($request->decided_at), $request->department?->code),
                    'action' => 'Buat PO',
                    'url' => route('purchase-orders.create', ['request' => $request->getKey()]),
                    'urgent' => false,
                ];
            }
        }

        if ($user->hasPermission(Permission::GOODS_RECEIPT_CREATE) || $user->hasPermission('*')) {
            $toReceive = PurchaseOrder::query()
                ->whereIn('status', [PurchaseOrderStatus::Issued, PurchaseOrderStatus::PartiallyReceived])
                ->with('supplier')
                ->orderBy('expected_at')
                ->get();

            foreach ($toReceive as $order) {
                $work[] = [
                    'number' => $order->number,
                    'title' => (string) $order->supplier?->name,
                    'meta' => $order->isOverdue()
                        ? sprintf('Terlambat sejak %s', $this->shortDate($order->expected_at))
                        : sprintf('Tiba %s · %s', $this->shortDate($order->expected_at), $order->status->label()),
                    'action' => 'Terima barang',
                    'url' => route('purchase-orders.show', $order),
                    'urgent' => $order->isOverdue(),
                ];
            }
        }

        $drafts = PurchaseRequest::query()
            ->where('requester_id', $user->getKey())
            ->where('status', PurchaseRequestStatus::Draft)
            ->latest()
            ->get();

        foreach ($drafts as $request) {
            $work[] = [
                'number' => $request->number,
                'title' => $request->purpose,
                'meta' => 'Draf, belum diajukan',
                'action' => 'Ajukan',
                'url' => route('purchase-requests.show', $request),
                'urgent' => false,
            ];
        }

        usort($work, fn (array $a, array $b): int => $b['urgent'] <=> $a['urgent']);

        return $work;
    }

    private function shortDate(mixed $date): string
    {
        return $date instanceof CarbonInterface ? $date->translatedFormat('j M') : '-';
    }

    private function requesterName(PurchaseRequest $request): string
    {
        $name = $request->requester?->name;

        return is_string($name) ? $name : '-';
    }
}
