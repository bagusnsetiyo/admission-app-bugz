<?php

namespace App\Http\Requests;

use App\Models\JadwalTes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AutoBatchPenugasanRequest — validasi auto-batch assign per prodi
 */
class AutoBatchPenugasanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jadwal_tes_id' => ['required', 'integer', 'exists:jadwal_tes,id'],
            'prodi'         => ['required', 'string', 'max:50'],
            'jenis'         => ['required', Rule::in([JadwalTes::JENIS_TES_SELEKSI, JadwalTes::JENIS_WAWANCARA])],
        ];
    }
}
