<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreRescheduleRequest — validasi permintaan reschedule dari peserta
 */
class StoreRescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_pendaftaran'    => ['required', 'string'],
            'penugasan_jadwal_id'  => ['required', 'integer', 'exists:penugasan_jadwal,id'],
            'jadwal_tes_baru_id'   => ['required', 'integer', 'exists:jadwal_tes,id'],
            'alasan'               => ['required', 'string', 'min:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.min' => 'Alasan reschedule minimal 20 karakter',
        ];
    }
}
