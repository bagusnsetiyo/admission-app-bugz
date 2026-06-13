<?php

namespace App\Http\Requests;

use App\Models\JadwalTes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateJadwalTesRequest — validasi update slot jadwal
 */
class UpdateJadwalTesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis'           => ['sometimes', Rule::in([JadwalTes::JENIS_TES_SELEKSI, JadwalTes::JENIS_WAWANCARA])],
            'judul'           => ['sometimes', 'string', 'max:100'],
            'tanggal_mulai'   => ['sometimes', 'date'],
            'tanggal_selesai' => ['sometimes', 'date'],
            'lokasi'          => ['sometimes', 'string', 'max:150'],
            'kapasitas'       => ['sometimes', 'integer', 'min:1', 'max:500'],
            'status'          => ['sometimes', Rule::in([JadwalTes::STATUS_AKTIF, JadwalTes::STATUS_NONAKTIF, JadwalTes::STATUS_SELESAI])],
            'catatan'         => ['nullable', 'string'],
        ];
    }
}
