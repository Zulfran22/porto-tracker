<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class KontrakCicilanRequest extends FormRequest
{
    /**
     * Ownership of the route-bound kontrak (for update/destroy) is enforced
     * in the controller via abort_if — this only confirms authentication,
     * which the 'auth' route middleware already guarantees.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shared between store() and update(). 'status' only applies to update()
     * — a newly-created contract always defaults to 'aktif' at the DB level.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'nomor_kontrak' => ['required', 'string', 'max:100'],
            'cabang' => ['nullable', 'string', 'max:100'],
            'no_rekening' => ['nullable', 'string', 'max:50'],
            'tanggal_mulai' => ['required', 'date'],
            'tenor_bulan' => ['required', 'integer', 'min:1', 'max:60'],
            'total_gram' => ['required', 'numeric', 'min:0'],
            'angsuran_bulan' => ['required', 'integer', 'min:0'],
            'sewa_modal' => ['nullable', 'integer', 'min:0'],
            'biaya_admin' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'file_kontrak' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];

        if ($this->route('kontrak')) {
            $rules['status'] = ['required', 'in:aktif,lunas,wanprestasi'];
        }

        return $rules;
    }
}
