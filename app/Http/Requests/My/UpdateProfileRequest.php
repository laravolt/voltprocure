<?php

declare(strict_types=1);

namespace App\Http\Requests\My;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array{name: list<string>, timezone: list<string>}
     */
    public static function profileRules(): array
    {
        return [
            'name' => ['required'],
            'timezone' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array{name: list<string>, timezone: list<string>}
     */
    public function rules(): array
    {
        return self::profileRules();
    }
}
