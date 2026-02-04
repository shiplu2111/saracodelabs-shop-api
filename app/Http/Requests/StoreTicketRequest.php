<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject'    => ['required', 'string', 'max:255'],
            'priority'   => ['required', 'in:low,medium,high'],
            'message'    => ['required', 'string'], // First message
            'attachment' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ];
    }
}
