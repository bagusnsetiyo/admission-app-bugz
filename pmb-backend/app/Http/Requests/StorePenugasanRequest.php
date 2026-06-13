<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StorePenugasanRequest — validasi assign manual pendaftar ke slot
 */
class StorePenugasanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jadwal_tes_id' => ['required', 'integer', 'exists:jadwal_tes,id'],
            'pendaftar_id'  => ['required', 'integer', 'exists:pendaftars,id'],
        ];
    }
}
