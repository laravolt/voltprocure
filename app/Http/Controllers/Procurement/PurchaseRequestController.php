<?php

declare(strict_types=1);

namespace App\Http\Controllers\Procurement;

use App\Actions\Procurement\CreatePurchaseRequest;
use App\Actions\Procurement\SubmitPurchaseRequest;
use App\Enums\Permission;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Queries\VisiblePurchaseRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class PurchaseRequestController
{
    /** Blank rows offered on the create form; empty rows are ignored. */
    private const int FORM_ROWS = 6;

    public function __construct(private VisiblePurchaseRequests $visible) {}

    public function index(Request $request): View
    {
        $user = $this->user($request);
        $status = PurchaseRequestStatus::tryFrom($request->string('status')->toString());

        $requests = $this->visible->for($user)
            ->when($status, fn (Builder $query, PurchaseRequestStatus $status): Builder => $query->where('status', $status))
            ->with(['department', 'requester', 'lines'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('procurement.purchase-requests.index', [
            'requests' => $requests,
            'status' => $status->value ?? '',
        ]);
    }

    public function create(): View
    {
        return view('procurement.purchase-requests.create', [
            'items' => Item::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get(),
            'rows' => self::FORM_ROWS,
        ]);
    }

    public function store(Request $request, CreatePurchaseRequest $create, SubmitPurchaseRequest $submit): RedirectResponse
    {
        $request->validate([
            'purpose' => ['required', 'string', 'max:160'],
            'needed_by' => ['nullable', 'date', 'after_or_equal:today'],
            'lines' => ['required', 'array', 'max:'.self::FORM_ROWS],
            'lines.*.item_id' => ['nullable', 'required_with:lines.*.quantity', 'distinct', 'exists:items,id'],
            'lines.*.quantity' => ['nullable', 'required_with:lines.*.item_id', 'integer', 'min:1', 'max:100000'],
        ], [
            'lines.*.item_id.required_with' => 'Pilih barangnya.',
            'lines.*.item_id.distinct' => 'Barang ini sudah ada di baris lain.',
            'lines.*.quantity.required_with' => 'Isi jumlahnya.',
        ], [
            'purpose' => 'keperluan',
            'needed_by' => 'tanggal dibutuhkan',
        ]);

        $lines = [];
        foreach ($request->array('lines') as $line) {
            if (is_array($line) && is_string($line['item_id'] ?? null) && is_numeric($line['quantity'] ?? null)) {
                $lines[] = ['item_id' => $line['item_id'], 'quantity' => (int) $line['quantity']];
            }
        }

        if ($lines === []) {
            return back()->withInput()->withErrors(['lines' => 'Tambahkan minimal satu barang.']);
        }

        $user = $this->user($request);

        $purchaseRequest = DB::transaction(function () use ($create, $submit, $request, $user, $lines): PurchaseRequest {
            $purchaseRequest = $create->handle($user, [
                'purpose' => $request->string('purpose')->trim()->toString(),
                'needed_by' => $request->filled('needed_by') ? $request->string('needed_by')->toString() : null,
                'lines' => $lines,
            ]);

            if ($request->input('intent') === 'submit') {
                $submit->handle($purchaseRequest, $user);
            }

            return $purchaseRequest;
        });

        $message = $request->input('intent') === 'submit'
            ? "{$purchaseRequest->number} diajukan ke manajer departemen."
            : "{$purchaseRequest->number} disimpan sebagai draf.";

        return to_route('purchase-requests.show', $purchaseRequest)->with('success', $message);
    }

    public function show(Request $request, PurchaseRequest $purchaseRequest): View
    {
        $user = $this->user($request);

        abort_unless($this->visible->for($user)->whereKey($purchaseRequest->getKey())->exists(), 404);

        $purchaseRequest->load(['department.manager', 'requester', 'decider', 'lines.item', 'purchaseOrder']);

        return view('procurement.purchase-requests.show', [
            'pr' => $purchaseRequest,
            'canSubmit' => $purchaseRequest->status === PurchaseRequestStatus::Draft && $purchaseRequest->requester_id === $user->getKey(),
            'canDecide' => $purchaseRequest->status === PurchaseRequestStatus::Submitted && $this->visible->canDecide($user, $purchaseRequest),
            'canOrder' => $purchaseRequest->status === PurchaseRequestStatus::Approved
                && ($user->hasPermission(Permission::PURCHASE_ORDER_MANAGE) || $user->hasPermission('*')),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
