<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GrantCashEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageStudents', $this->route('course'));
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'amount_paid' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
