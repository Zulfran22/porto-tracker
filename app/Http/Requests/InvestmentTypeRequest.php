<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InvestmentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * No 'unit' field on purpose — the controller always hardcodes unit='rupiah'.
     * This structurally prevents a second gram-unit type from ever being
     * created through this endpoint (only the seeded "Emas Tunai" may hold
     * gram data — see InvestmentTypePolicy).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
        ];
    }
}
