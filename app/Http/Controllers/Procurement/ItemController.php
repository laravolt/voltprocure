<?php

declare(strict_types=1);

namespace App\Http\Controllers\Procurement;

use App\Models\Item;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ItemController
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $lowOnly = $request->query('stock') === 'low';

        $items = Item::query()
            ->where('is_active', true)
            ->when($search !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $match): Builder => $match->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"),
            ))
            ->when($lowOnly, fn (Builder $query): Builder => $query
                ->where('reorder_point', '>', 0)
                ->whereColumn('stock_on_hand', '<=', 'reorder_point'))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('procurement.items.index', [
            'items' => $items,
            'search' => $search,
            'stock' => $lowOnly ? 'low' : '',
        ]);
    }

    public function show(Item $item): View
    {
        return view('procurement.items.show', [
            'item' => $item,
            'movements' => $item->stockMovements()->with(['source', 'creator'])->latest('moved_at')->limit(50)->get(),
        ]);
    }
}
