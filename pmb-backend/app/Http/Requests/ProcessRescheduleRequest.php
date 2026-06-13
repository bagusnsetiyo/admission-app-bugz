<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ProcessRescheduleRequest — validasi approve/reject reschedule oleh admin
 */
class ProcessRescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'             => ['required', Rule::in(['approve', 'reject'])],
            'alasan_penolakan'   => ['required_if:action,reject', 'nullable', 'string', 'min:10'],
        ];
    }
}
