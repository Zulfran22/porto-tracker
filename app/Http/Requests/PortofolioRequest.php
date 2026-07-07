<?php

namespace App\Http\Requests;

use App\Models\KontrakCicilanEmas;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

        // harga_emas is only meaningful (and required) if this month actually has
        // gold-related data: a gram-unit item with gram > 0, or an active kontrak
        // cicilan whose BEP/valuation also depends on it. Otherwise a user tracking
        // only e.g. a custom "Saham" rupiah type shouldn't be forced to enter a
        // gold price that's irrelevant to them.
        $items = collect($this->input('items', []));
        $hasGramItem = $items->contains(fn ($i) => ($i['unit'] ?? null) === 'gram' && (float) ($i['gram'] ?? 0) > 0);
        $hasKontrakAktif = KontrakCicilanEmas::aktifUntuk($this->user()->id) !== null;

        $hargaEmasRules = ($hasGramItem || $hasKontrakAktif)
            ? ['required', 'integer', 'min:0']
            : ['nullable', 'integer', 'min:0'];

        return [
            'bulan' => $bulanRules,
            'harga_emas' => $hargaEmasRules,
            'cicilan' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'items' => ['array'],
            'items.*.type_name' => ['required', 'string', 'max:50'],
            'items.*.unit' => ['required', Rule::in(['rupiah', 'gram'])],
            'items.*.gram' => ['nullable', 'numeric', 'min:0'],
            'items.*.jumlah' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'bulan.unique' => 'Sudah ada data untuk bulan itu.',
        ];
    }

    /**
     * Defense in depth: the UI only ever produces one gram-unit item (the
     * seeded "Emas Tunai" row), but a tampered request shouldn't be able to
     * smuggle a second one in.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $gramCount = collect($this->input('items', []))
                ->filter(fn ($i) => ($i['unit'] ?? null) === 'gram')
                ->count();

            if ($gramCount > 1) {
                $validator->errors()->add('items', 'Hanya boleh ada satu jenis investasi bersatuan gram.');
            }
        });
    }
}
