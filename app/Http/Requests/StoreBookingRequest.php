<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s-]{7,20}$/'],
            'purpose' => ['required', 'string', 'max:255'],
            'dates' => ['required', 'array', 'min:1', 'max:31'],
            'dates.*' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'dates.required' => 'Please select at least one date.',
            'dates.*.after_or_equal' => 'You can only book from today onward.',
            'mobile_number.regex' => 'Please enter a valid mobile number.',
        ];
    }
}
