<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CekStatusRequest — validasi cek status dengan verifikasi nomor HP
 */
class CekStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_pendaftaran' => ['required', 'string', 'regex:/^PMB-[0-9]{4}-[0-9]{4}$/'],
            'verifikasi_hp'     => ['required', 'string', 'regex:/^\d{4,13}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_pendaftaran.regex' => 'Format nomor pendaftaran tidak valid',
            'verifikasi_hp.regex'     => 'Verifikasi HP harus 4–13 digit angka',
        ];
    }
}
