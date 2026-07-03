<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortofolioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Ownership of the route-bound Portofolio (for update) is enforced by
     * PortofolioPolicy via $this->authorize() in the controller — this only
     * confirms the user is authenticated, which the 'auth' route middleware
     * already guarantees.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Shared between store() and update() — the rules are identical for both.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bulanRules = ['required', 'date_format:Y-m'];

        // Only relevant on update: store() intentionally treats an existing
        // month as "update that row" (see PortofolioController::store()),
        // but update() can retarget a different month, which must not
        // silently collide with another (non-deleted) row for this user.
        $portofolio = $this->route('portofolio');
        if ($portofolio) {
            $bulanRules[] = Rule::unique('portofolios')
                ->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at'))
                ->ignore($portofolio->id);
        }

        return [
            'bulan' => $bulanRules,
            'emas_gram' => ['required', 'numeric', 'min:0'],
            'harga_emas' => ['required', 'integer', 'min:0'],
            'cicilan' => ['required', 'integer', 'min:0'],
            'dana_darurat' => ['nullable', 'integer', 'min:0'],
            'reksa_dana' => ['nullable', 'integer', 'min:0'],
            'sbn' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'bulan.unique' => 'Sudah ada data untuk bulan itu.',
        ];
    }
}
