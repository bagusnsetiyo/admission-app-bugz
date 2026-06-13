<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * VerifyJadwalRequest — validasi akses jadwal peserta dengan verifikasi HP
 */
class VerifyJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verifikasi_hp' => ['required', 'string', 'regex:/^\d{4,13}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'verifikasi_hp.regex' => 'Verifikasi HP harus 4–13 digit angka',
        ];
    }
}
