<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CheckinRequest — validasi check-in operator via QR token
 */
class CheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'        => ['required', 'string', 'size:36'],
            'operator_pin' => ['required', 'string'],
        ];
    }
}
