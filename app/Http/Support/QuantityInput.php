<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Http\Request;

/**
 * Reads a `field[<line id>] = <number>` map from a form into id => int.
 * Blank entries are left out so the action can apply its own default.
 */
final class QuantityInput
{
    /**
     * @return array<string, int>
     */
    public static function from(Request $request, string $field): array
    {
        $quantities = [];

        foreach ($request->array($field) as $id => $value) {
            if (is_numeric($value)) {
                $quantities[(string) $id] = (int) $value;
            }
        }

        return $quantities;
    }
}
