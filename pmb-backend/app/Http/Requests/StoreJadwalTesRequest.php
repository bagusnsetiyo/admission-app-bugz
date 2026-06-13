<?php

namespace App\Http\Requests;

use App\Models\JadwalTes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreJadwalTesRequest — validasi pembuatan slot jadwal baru
 */
class StoreJadwalTesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis'           => ['required', Rule::in([JadwalTes::JENIS_TES_SELEKSI, JadwalTes::JENIS_WAWANCARA])],
            'judul'           => ['required', 'string', 'max:100'],
            'tanggal_mulai'   => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'lokasi'          => ['required', 'string', 'max:150'],
            'kapasitas'       => ['required', 'integer', 'min:1', 'max:500'],
            'catatan'         => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.required'           => 'Jenis jadwal wajib dipilih',
            'judul.required'           => 'Judul jadwal wajib diisi',
            'tanggal_mulai.required'   => 'Tanggal mulai wajib diisi',
            'tanggal_selesai.after'    => 'Tanggal selesai harus setelah tanggal mulai',
            'lokasi.required'          => 'Lokasi wajib diisi',
            'kapasitas.min'            => 'Kapasitas minimal 1 peserta',
        ];
    }
}
